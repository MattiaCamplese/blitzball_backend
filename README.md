# Blitzball Tournament Manager - Backend API

Backend REST API in PHP per la gestione completa di tornei di calcetto (futsal). Fornisce endpoint per gestione atleti, squadre, tornei, bracket ad eliminazione diretta e Hall of Fame.

## Tecnologie Utilizzate

- **PHP 8.1+** - Linguaggio backend con tipizzazione moderna
- **PostgreSQL** - Database relazionale robusto
- **Composer** - Dependency manager PHP
- **Pecee Simple Router** - Router HTTP leggero e veloce
- **PDO** - Database abstraction layer sicuro
- **Active Record Pattern** - ORM custom per query intuitive

## Architettura

Il progetto segue un'architettura MVC semplificata con pattern Active Record:

```
Model ↔ Database
  ↓
Service (Business Logic)
  ↓
Route (HTTP Controller)
  ↓
Response (JSON)
```

## Funzionalità API

### 🏆 Tornei (`/api/tournaments`)
- `GET /tournaments` - Lista tutti i tornei
- `GET /tournaments/{id}` - Dettaglio torneo
- `POST /tournaments` - Crea nuovo torneo
- `PUT/PATCH /tournaments/{id}` - Aggiorna torneo
- `DELETE /tournaments/{id}` - Elimina torneo

### ⚽ Squadre (`/api/teams`)
- `GET /teams` - Lista tutte le squadre
- `GET /teams/{id}` - Dettaglio squadra
- `POST /teams` - Crea nuova squadra
- `PUT/PATCH /teams/{id}` - Aggiorna squadra
- `DELETE /teams/{id}` - Elimina squadra

### 👥 Atleti (`/api/athletes`)
- `GET /athletes` - Lista tutti gli atleti
- `GET /athletes/{id}` - Dettaglio atleta
- `POST /athletes` - Crea nuovo atleta
- `PUT/PATCH /athletes/{id}` - Aggiorna atleta
- `DELETE /athletes/{id}` - Elimina atleta

### 🔗 Composizioni (`/api/compositions`)
Relazioni Atleta ↔ Squadra con storico
- `GET /compositions` - Lista tutte le composizioni
- `GET /compositions/athlete/{id}` - Composizioni di un atleta
- `GET /compositions/team/{id}` - Composizioni di una squadra
- `POST /compositions` - Crea nuova composizione
- `PUT /compositions/{id}/terminate` - Termina composizione (setta end_date)
- `DELETE /compositions/{id}` - Elimina composizione

### 📝 Registrazioni (`/api/registrations`)
Iscrizioni squadre ai tornei
- `GET /registrations` - Lista tutte le registrazioni
- `GET /registrations/tournament/{id}` - Registrazioni di un torneo
- `POST /registrations` - Iscrive squadra a torneo
- `DELETE /registrations/{id}` - Elimina registrazione

### 🎮 Partite (`/api/games`)
- `GET /games` - Lista tutte le partite
- `GET /games/{id}` - Dettaglio partita
- `GET /games/tournament/{id}` - Partite di un torneo
- `PUT/PATCH /games/{id}` - Aggiorna punteggio partita
- `DELETE /games/{id}` - Elimina partita

### 🎯 Bracket Generator (`/api/brackets`)
Generazione e gestione tabelloni ad eliminazione diretta
- `POST /brackets/generate/{tournamentId}` - Genera bracket per torneo
- `PUT /brackets/game/{gameId}/result` - Aggiorna risultato partita
  - Valida no-pareggi
  - Avanza vincitore al round successivo
  - Crea automaticamente Hall of Fame se finale

### 🏅 Hall of Fame (`/api/hall-of-fame`)
- `GET /hall-of-fame` - Lista tutti i vincitori
- `GET /hall-of-fame/{id}` - Dettaglio vincitore
- `POST /hall-of-fame` - Aggiunge vincitore (automatico da bracket)
- `DELETE /hall-of-fame/{id}` - Elimina entry

## Struttura del Progetto

```
Back/
├── config/                    # Configurazioni
│   ├── database.php          # Connessione PostgreSQL
│   └── cors.php              # Configurazione CORS
│
├── public/                    # Document root
│   └── index.php             # Entry point applicazione
│
├── routes/                    # Definizione route HTTP
│   ├── index.php             # Router principale
│   ├── athletes.php          # Route atleti
│   ├── teams.php             # Route squadre
│   ├── tournaments.php       # Route tornei
│   ├── compositions.php      # Route composizioni
│   ├── registrations.php     # Route registrazioni
│   ├── games.php             # Route partite
│   ├── brackets_generator.php # Route bracket generator
│   └── halls_of_fame.php     # Route hall of fame
│
├── src/
│   ├── bootstrap.php         # Bootstrap applicazione
│   │
│   ├── Database/             # Layer database
│   │   ├── DB.php           # Classe DB (PDO wrapper)
│   │   └── JSONDB.php       # JSON DB (fallback/testing)
│   │
│   ├── Models/               # Active Record Models
│   │   ├── BaseModel.php    # Classe base ORM
│   │   ├── Athlete.php      # Model atleta
│   │   ├── Team.php         # Model squadra
│   │   ├── Tournament.php   # Model torneo
│   │   ├── Composition.php  # Model composizione
│   │   ├── Registration.php # Model registrazione
│   │   ├── Game.php         # Model partita
│   │   └── HallOfFame.php   # Model hall of fame
│   │
│   ├── Services/             # Business Logic
│   │   ├── BracketGenerator.php   # Generazione bracket
│   │   └── CompositionService.php # Logica composizioni
│   │
│   ├── Traits/               # PHP Traits riutilizzabili
│   │   ├── WithValidate.php # Validazione dati
│   │   └── HasRelations.php # Relazioni tra modelli
│   │
│   └── Utils/                # Utility
│       ├── Request.php       # Gestione richieste HTTP
│       └── Response.php      # Gestione risposte JSON
│
├── Database/                  # Schema database
│   └── blitzball.sql         # Script creazione tabelle
│
├── vendor/                    # Dipendenze Composer
│
├── composer.json              # Configurazione Composer
├── composer.lock              # Lock dipendenze
└── README.md                  # Questo file
```

## Dettaglio Cartelle

### `/config` - Configurazioni
**`database.php`** - Configurazione connessione PostgreSQL
```php
return [
    'host' => 'localhost',
    'port' => 5432,
    'database' => 'blitzball',
    'username' => 'postgres',
    'password' => 'password',
];
```

**`cors.php`** - Configurazione CORS per frontend
```php
return [
    'allowed_origins' => ['http://localhost:5173'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
];
```


**Models disponibili:**
- **Athlete** - Anagrafica atleti
- **Team** - Squadre con logo e tornei vinti
- **Tournament** - Tornei con date e numero squadre
- **Composition** - Relazione Atleta-Squadra (storico)
- **Registration** - Iscrizione Squadra-Torneo
- **Game** - Partite del bracket
- **HallOfFame** - Vincitori tornei

### `/src/Services` - Business Logic

**`BracketGenerator.php`** - Logica generazione bracket
```php
class BracketGenerator
{
    // Genera bracket completo per torneo
    public static function generateBracket(
        int $tournamentId,
        array $teamIds
    ): array

    // Aggiorna risultato e avanza vincitore
    public static function updateGameResult(
        Game $game,
        int $homeScore,
        int $awayScore
    ): int

    // Trova partita successiva per vincitore
    public static function findNextGame(
        int $tournamentId,
        int $currentGameId
    ): ?Game

    // Verifica se è la finale
    public static function isFinalGame(
        array $games,
        int $gameId
    ): bool
}
```

**Algoritmo Bracket:**
1. Calcola numero round: `log2(teamCount)`
2. Dispone squadre con algoritmo a specchio
3. Crea partite per tutti i round
4. Calcola spacing verticale: `cardHeight * 2^roundIndex - cardHeight`
5. Assegna `next_game_fk` per propagazione vincitori

**`CompositionService.php`** - Gestione composizioni
- Validazione date
- Verifica conflitti (un atleta = una squadra attiva)
- Terminazione composizioni

### `/src/Traits` - Codice Riutilizzabile

**`WithValidate.php`** - Validazione dati
```php
trait WithValidate
{
    public static function validate(array $data): array
    {
        // Controlla rules definiti nel model
        // Supporta: required, min, max, regex, unique, integer, etc.
        // Ritorna array errori o array vuoto
    }
}
```

**`HasRelations.php`** - Relazioni tra modelli (futuro)

### `/src/Utils` - Utility

**`Request.php`** - Gestione richieste HTTP
```php
class Request
{
    public function json(): array  // Parse body JSON
    public function get($key, $default = null)
    public function post($key, $default = null)
    public function header($key)
}
```

**`Response.php`** - Risposte JSON standardizzate
```php
class Response
{
    public static function success(
        mixed $data = null,
        int $statusCode = 200,
        string $message = ''
    ): self

    public static function error(
        string $message,
        int $statusCode = 500,
        mixed $errors = null
    ): self

    public function send(): void  // Invia risposta
}
```
```
```
### `HTTP Status Codes` Utilizzati
- `200 OK` - Operazione riuscita
- `201 Created` - Risorsa creata
- `400 Bad Request` - Errore validazione
- `404 Not Found` - Risorsa non trovata
- `500 Internal Server Error` - Errore server


### Relazioni Database
```
```
ATHLETE ←→ COMPOSITION ←→ TEAM
                            ↓
TOURNAMENT ←→ REGISTRATION ←┘
    ↓
  GAME (bracket)
    ↓
HALL_OF_FAME
```
```
## Installazione e Setup
```
### Prerequisiti
- PHP >= 8.1
- PostgreSQL >= 13
- Composer
- Server web (Apache/Nginx) o PHP built-in server

```
### 1. Clona e installa dipendenze
```bash
cd Back/
composer install
```

### 2. Configura database
Modifica `config/database.php`:
```php
return [
    'host' => 'localhost',
    'port' => 5432, // 3306 per MySQL, 5432 per PostgreSQL
    'database' => 'blitzball',
    'username' => 'postgres',
    'password' => 'admin',
]
```


### 3. Avvia server
```bash
# PHP built-in server
php -S localhost:8000 -t public

# Oppure configura Apache/Nginx con DocumentRoot su /public
```

API disponibile su: `http://localhost:8000/api`


**Errori Comuni:**
- `404` - Risorsa non trovata
- `400` - Validazione fallita
- `500` - Errore database/server
- `409` - Conflitto (es. nome duplicato)

## Comandi Utili

```bash
# Installa dipendenze
composer install

# Aggiorna autoload dopo nuove classi
composer dump-autoload

# Avvia server sviluppo
php -S localhost:8000 -t public

# Backup database
pg_dump blitzball > backup.sql


## Estensioni Future

### Potenziali migliorie
- [ ] Autenticazione utenti (admin/organizzatore)
- [ ] Upload immagini logo squadre
- [ ] Statistiche avanzate (goal, cartellini)
- [ ] Export PDF bracket/risultati
- [ ] Notifiche email vincitori

## Licenza

Progetto didattico - Uso educativo

---

**API Documentation completa disponibile negli endpoint** 🚀
