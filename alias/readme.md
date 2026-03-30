# 🕵️ Alias (Spyfall) — Online Party Game

🌐 **Live Demo:** http://www.o-planet.ru/alias/

📋 Description
Alias is an exciting online spy game perfect for parties and online gatherings with friends. All participants except one receive secret information about a specific location. One randomly selected player becomes the Spy who doesn't know the location.

Players take turns asking each other leading questions related to this place, trying to figure out who among them is the outsider. The Spy must listen carefully to answers and guess the location without blowing their cover. At the end of the round, voting takes place — the team tries to catch the Spy, while the Spy tries to guess the location.

> 💡 **About the Project:** This game was created to demonstrate and promote the **LOTIS** PHP framework. 
> 🔗 Framework: https://github.com/O-Planet/LOTIS

## 🎯 Game Objectives
- **For regular players:** Identify the Spy by their suspicious answers and behavior
- **For the Spy:** Either remain undetected or correctly guess the location the others are talking about

## ✨ Features
- Support for up to 1000 players in a single game
- Simple connection via game code — create a session and invite friends
- Automatic selection of Spy and location
- Built-in chat for communication
- Voting system with timer
- Database of hundreds of diverse locations (from beaches to spaceports)
- "Merlin" — virtual assistant asking leading questions

## 🚀 Installation

### Requirements
- PHP 7.4 or higher
- MySQL / MariaDB
- Web server (Apache/Nginx)
- LOTOS framework

### Step-by-Step Guide

#### 1. Clone the Repository
```bash
git clone [repository-url]
# or download the archive and extract it
```

Project structure should be:
```
src/
├── newlotis/          # LOTOS framework
└── alias/             # Game files (this repository)
```

#### 2. Configure Database Connection
Open `connect.php` and set your database parameters:

```php
<?php
$databasename = 'alias';        // Database name
$databaseserver = 'localhost';  // Server address
$databaseuser = 'root';         // MySQL user
$databasepassword = 'root';     // Password
?>
```

#### 3. Create Database Tables
Open the following URL in your browser to automatically create the database structure:
```
http://your-site/alias/sdb.php?updatereg=create
```

**Done!** The game is installed and ready to run.

## 🎮 How to Play

1. **Create Game:** The admin creates a game and receives a unique code
2. **Join:** Players enter the code and their name to join
3. **Start:** After at least 3 players connect, the admin starts the game
4. **Gameplay:**
   - Everyone except the Spy sees the secret location
   - Players ask each other questions ("Can you buy coffee there?")
   - The Spy answers without knowing the location but tries not to give themselves away
5. **Voting:** When time runs out, everyone votes on who the Spy is
6. **Results:** The team wins (if they caught the Spy) or the Spy wins (if they guessed the location or remained undetected)

## 🛠 Technical Details

- **Language:** PHP (LOTOS framework)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript (jQuery)
- **Data files:**
  - `secrets.d` — list of locations
  - `vopr.d` — database of Merlin's questions
  - `spy.d` — humorous spy tips

## 📄 License

Open source project. Free to use and modify.

## 🔗 Links

- 🎮 **Play Online:** http://www.o-planet.ru/alias/
- 🛠 **LOTIS Framework:** https://github.com/O-Planet/LOTIS
