# I Just Want To Be Heard

An interactive animated story about online grooming, family conflict and feeling alone, made as a Capstone Project (EMC) at the Technological Institute of the Philippines.

**Live site:** https://just-want-to-be-heard.onrender.com

> **Content note:** this story deals with online grooming, family conflict and loneliness. Viewers can pause or leave at any time, and a **Need to talk?** button is always on screen.

---

## Purpose

Young people who feel unheard at home are easy targets for adults who groom them online: someone who always listens, flatters them, and slowly pulls them away from the people who care about them.

This project helps viewers **recognise those warning signs before they are in danger**. They don't read a lecture. They follow Riley, a teenager who just wants someone to listen, and learn by doing:

- **Empathy:** viewers see what loneliness looks like from Riley's side, so they understand why an online "friend" can feel so appealing.
- **Spotting red flags:** viewers search each scene for warning signs, such as flattery, secrecy, "your parents don't get you like I do", and requests to meet. Finding them builds a habit they can use in real life.
- **Safe practice with choices:** viewers decide what Riley does next and see where each choice leads, without real-world consequences.
- **No blame:** the story says clearly that grooming is never the victim's fault, and points viewers to people and services who can help.

## What the viewer experiences

The story plays as a full-screen animation, scene by scene.

1. **Watch:** each scene plays like a short film.
2. **Explore:** the animation pauses and **glowing hotspots** appear on objects in the frame. Clicking one opens a card:
   - *Look closer*: what is really going on in the scene.
   - *Riley's thought*: Riley's inner voice.
   - *Red flag*: a grooming warning sign. Each one found adds to the **Red flags found** score.
3. **Pause and think:** reflection questions after a scene. Every answer gets kind, explained feedback, and written answers stay private in the viewer's browser.
4. **Choose:** decision points where the viewer picks what Riley does next.
5. **Endings:** a good or bad ending with a recap of the viewer's choices and the red flags they found. The bad ending offers **Go back to your last choice** and reminds viewers it is not Riley's fault.

## The admin studio

The story is fully editable without code, in a login-protected admin area at `/admin/`:

| Section | What you do there |
|---|---|
| **Scenes** | Order the story, assign each scene's animation, set the next scene, choice question or ending |
| **Videos** | Upload the animation files (MP4, 1920 × 1080) |
| **Hotspots** | Draw clickable areas on the video frame and write their pop-up cards |
| **Reflection Questions** | Add questions with an explanation for every answer |
| **Story Choices** | Add the choices at each decision point and where they lead |
| **Settings** | Project text, branding, content note and help resources |
| **Tutorials** | Short screen-recorded walkthroughs of the admin panel |

**Play the Story** in the sidebar opens the player for testing.

## Built with

- PHP 8 (no framework) and MySQL / MariaDB
- Plain HTML, CSS and JavaScript for the player and admin
- Docker (PHP 8.2 + Apache) for hosting on [Render](https://render.com), with the database on [Aiven](https://aiven.io)

## Run it locally (XAMPP)

1. Put this folder in `C:\xampp\htdocs\just-want-to-be-heard` and start **Apache** and **MySQL** in XAMPP.
2. In phpMyAdmin (or the MySQL command line), import `database/schema.sql`, then `database/demo_seed.sql` for the six-scene demo story.
3. Open http://localhost/just-want-to-be-heard/.
4. Open http://localhost/just-want-to-be-heard/admin/. The first visit asks you to create the admin account.

The database settings default to XAMPP's (`root`, no password, database `just_want_to_be_heard`). See [INTERACTIVITY-GUIDE.md](INTERACTIVITY-GUIDE.md) for how to build scenes, and [TUTORIAL.md](TUTORIAL.md) for a step-by-step guide to adding your own animation and hotspots.

## Deploy online

The repository is ready for Render:

- `Dockerfile` builds the site; `render.yaml` is a Render Blueprint with the settings filled in.
- The app reads `APP_URL`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SSL` and `DB_SSL_CA` from environment variables, and falls back to the XAMPP values when they are not set.
- `database/import.php` loads the schema and demo story into a hosted MySQL 8 database from the command line.

On a fresh deployment, open `/admin/` straight away and create the admin account: the first visitor to the setup page becomes the admin.

Videos uploaded through the live admin are lost when Render redeploys, because its disk is temporary. Commit final animations to `uploads/videos/` instead.

## Project structure

```
admin/          Admin studio (scenes, videos, hotspots, questions, choices, settings, tutorials)
viewer/         The interactive player
assets/         CSS, JavaScript and tutorial videos
config/         App and database settings (environment variables with XAMPP fallbacks)
database/       schema.sql, demo_seed.sql and the import script
includes/       Login, schema upgrades and admin layout
uploads/        Animations and images (demo placeholders in uploads/videos/demo/)
```

## Before showing it publicly

- Check every phone number in **Settings → Public Experience → Help Resources** with your adviser.
- Replace the placeholder demo videos and text with the final animation.
