# Interactive Player Guide

The viewer (`viewer/scene.php`) is now a full-screen animation player. The story plays as video,
and the website around it stays out of the way.

## What the viewer experiences

1. **Watch**: each scene plays full screen. Only small buttons stay on top: leave, pause, mute, full screen, and **Need to talk?**
2. **Explore (Hotspots)**: at a set time, or when the scene ends, the animation pauses and glowing spots appear on objects in the frame. Clicking one opens a pop-up card.
   - *Look closer*: a general insight.
   - *Riley's thought*: Riley's inner voice.
   - *Red flag*: counts toward the "Red flags found" score shown at the top.
3. **Pause and Think (Reflection Questions)**: after a scene, the viewer answers a question. Every answer gets a kind explanation. Open answers stay in the browser and are never sent to the server.
4. **Choose (Story Choices)**: Telltale-style buttons decide which scene plays next.
5. **Endings**: a scene marked Good/Bad Ending shows an ending card with the viewer's journey (their choices and red flags found). The Bad Ending says clearly that it is not Riley's fault and offers **Go back to your last choice**.

## How to build a scene in the admin

1. **Videos**: upload the scene's animation. Export at **1920 x 1080** (16:9). Hotspot positions are measured in that frame, so a vertical or different-size video will not line up.
2. **Scenes**: assign the video and set *Next Scene* for linear parts. For a decision scene, fill in *Choice Question*. For the last scene of a path, set *Ending* to Good or Bad.
3. **Hotspots**: pick the scene, then scrub the preview and drag a box over the object. Set *Appears At* (seconds) or leave it empty to show the hotspot when the scene ends. Write the pop-up title and text.
4. **Reflection Questions**: one answer per line, written as `answer || feedback`.
5. **Story Choices**: add two or more choices to a scene, each leading to a scene.
6. Click **Play the Story** in the sidebar to test.

Order after a scene ends: end-of-scene hotspots, then reflection questions, then choices (or the ending, or the next scene).

## Demo story

`database/demo_seed.sql` loads a six-scene demo (placeholder videos in `uploads/videos/demo/`) that uses every feature, based on the storyboard. Import it into an **empty** database after `schema.sql` to try it, then replace the videos and text with your own.

## Upgrading an existing database

Nothing to run by hand. The first page load adds the new tables and columns automatically (`includes/schema_upgrade.php`). Fresh installs can import `database/schema.sql` as before.

## Before showing it publicly

- Check every phone number in **Settings > Public Experience > Help Resources** with your adviser.
- The admin area needs a login. On a fresh install the first person to open `/admin/setup.php` creates the admin account, so open it yourself right after deploying.
