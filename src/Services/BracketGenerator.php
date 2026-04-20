<?php

namespace App\Services;

use App\Models\Game;
use App\Models\HallOfFame;
use App\Models\Tournament;

class BracketGenerator
{
    public static function generateBracket(Tournament $tournament, array $teamIds): array
    {
        $validNumbers = [4, 8, 16, 32];
        $numTeams = count($teamIds);

        if (!in_array($numTeams, $validNumbers)) {
            throw new \Exception("Numero di team non valido: " . $numTeams);
        }

        shuffle($teamIds);

        $games = [];
        $round = 1;
        $matchNumber = 1;
        $totalRounds = (int) log($numTeams, 2);

        $positionInRound = 1;
        for ($i = 0; $i < $numTeams; $i += 2) {
            $game = new Game();
            $game->tournament_fk = $tournament->id;
            $game->match_number = $matchNumber++;
            $game->round = $round;
            $game->position_in_round = $positionInRound++;
            $game->home_team_fk = $teamIds[$i];
            $game->away_team_fk = $teamIds[$i + 1];
            $game->completed = false;
            $game->save();
            $games[] = $game;
        }

        for ($r = 2; $r <= $totalRounds; $r++) {
            $numGames = $numTeams / (2 ** $r);
            if ($numGames < 1) $numGames = 1;
            $positionInRound = 1;
            for ($i = 0; $i < $numGames; $i++) {
                $game = new Game();
                $game->tournament_fk = $tournament->id;
                $game->match_number = $matchNumber++;
                $game->round = $r;
                $game->position_in_round = $positionInRound++;
                $game->completed = false;
                $game->save();
                $games[] = $game;
            }
        }

        return $games;
    }

    public static function updateGameResult(Game $game, int $homeScore, int $awayScore): int
    {
        if ($homeScore === $awayScore) {
            throw new \Exception('Pareggio non consentito in una partita ad eliminazione diretta');
        }

        $game->home_score = $homeScore;
        $game->away_score = $awayScore;
        $game->completed = true;
        $game->save();

        $winnerTeamId = $homeScore > $awayScore
            ? $game->home_team_fk
            : $game->away_team_fk;

        $tournamentGames = Game::where('tournament_fk', (int)$game->tournament_fk);

        if (empty($tournamentGames)) {
            throw new \Exception('Nessuna partita trovata per questo torneo');
        }

        $gamesInThisRound = array_filter($tournamentGames, fn($g) => $g->round === $game->round);

        if (count($gamesInThisRound) === 1) {
            $hof = new HallOfFame([
                'tournament_fk'   => $game->tournament_fk,
                'winning_team_fk' => $winnerTeamId,
                'victory_date'    => date('Y-m-d'),
            ]);
            $hof->save();
            $hof->incrementTeamTournaments();

            $tournament = Tournament::find($game->tournament_fk);
            if ($tournament) {
                $tournament->is_active = false;
                $tournament->save();
            }

            return $winnerTeamId;
        }

        $nextRound = $game->round + 1;
        $nextPosition = (int) ceil($game->position_in_round / 2);

        $nextGame = null;
        foreach ($tournamentGames as $g) {
            if ($g->round === $nextRound && $g->position_in_round === $nextPosition) {
                $nextGame = $g;
                break;
            }
        }

        if (!$nextGame) {
            $maxMatchNumber = max(array_map(fn($g) => $g->match_number, $tournamentGames));
            $nextGame = new Game();
            $nextGame->tournament_fk = $game->tournament_fk;
            $nextGame->match_number = $maxMatchNumber + 1;
            $nextGame->round = $nextRound;
            $nextGame->position_in_round = $nextPosition;
            $nextGame->completed = false;
        }

        if ($game->position_in_round % 2 === 1) {
            $nextGame->home_team_fk = $winnerTeamId;
        } else {
            $nextGame->away_team_fk = $winnerTeamId;
        }

        $nextGame->save();

        return $winnerTeamId;
    }

    public static function getFinal(int $tournamentId): ?Game
    {
        $games = Game::where('tournament_fk', $tournamentId);

        if (empty($games)) return null;

        $maxRound = max(array_map(fn($g) => $g->round, $games));

        foreach ($games as $g) {
            if ($g->round === $maxRound && $g->completed) {
                return $g;
            }
        }

        return null;
    }
}