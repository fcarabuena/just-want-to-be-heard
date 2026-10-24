# Tutorial: Put Your Own Animation in Scene 1 and Add a Hotspot

This guide walks you through the admin panel one step at a time. By the end you will have:

1. uploaded your own animation video,
2. put it in **Scene 1** (the scene called "Unheard"),
3. placed a glowing **hotspot** on an object in the video,
4. tested it in the player.

The names in **bold** are the exact words you will see on the screen.

---

## Before you start

Check these three things first. They prevent most problems.

- **XAMPP is running.** In the XAMPP Control Panel, **Apache** and **MySQL** should both be green.
- **Your video is an MP4, 1920 × 1080 (16:9).** Hotspot positions are measured on a 1920 × 1080 frame. A vertical video or a different shape will make hotspots land in the wrong place.
- **Your video is smaller than 40 MB.** XAMPP is set to reject uploads larger than 40 MB. If your file is bigger, export it again at a lower quality (bitrate), or ask for help raising the limit.

The admin panel is here: **http://localhost/just-want-to-be-heard/admin/**

It asks you to sign in first. Use your admin username (or email) and password. If no admin account exists yet, it opens a one-time setup page where you create one.

On the left side of every admin page there is a menu (the sidebar). You will use these items:

| Sidebar item | What it is for |
|---|---|
| **▶ Videos** | Upload video files |
| **🎬 Scenes** | Choose which video each scene plays |
| **◎ Hotspots** | Add clickable glowing spots to a scene |
| **▷ Play the Story** | Open the player to test |

---

## Step 1: Upload your video

1. Open **http://localhost/just-want-to-be-heard/admin/videos/index.php**, or click **▶ Videos** in the sidebar.
2. You are on the **Video Library** page. Click the gold **+ Create New Video** button at the top.
3. You are now on the **Create Video** page. Fill in:

| Field | What to do |
|---|---|
| **Video Title** | Required. Type a name you will recognise, for example `Scene 1 - Unheard (final)` |
| **Video File** | Required. Click **Choose File** and pick your MP4 |
| **Selected File**, **File Size**, **Duration** | Nothing to do. These fill in by themselves after you pick the file, so you can check it is the right one |
| **Published** | Tick this box |

4. Click **Save Video**. Wait. A big file can take a while, so don't click twice.

**What you should see:** you are back on the **Video Library** page, and your video is the first card in the list.

**If it goes wrong:**
- *"Video title is required."* even though you typed a title: the file was bigger than 40 MB, so the upload was dropped. Use a smaller file.
- *"Unsupported video file type…"*: the file is not an MP4 (or WebM, MOV, MKV, AVI). Export it as MP4.

---

## Step 2: Put the video in Scene 1

1. Click **🎬 Scenes** in the sidebar, or open **http://localhost/just-want-to-be-heard/admin/scenes/index.php**.
2. You are on the **Scene Library** page. Find the card for scene **1 – Unheard** and click its **Edit** button.
3. You are on the **Edit Scene** page. Change **only one field**:

| Field | What to do |
|---|---|
| **Assigned Animation** | Open the dropdown and pick the video you uploaded in Step 1 |
| Everything else (**Scene Number**, **Display Order**, **Title**, **Description**, **Status**, **Next Scene**, **Ending**, **Choice Question**, **Interactive Scene**) | Leave it as it is. The demo already set these correctly (Status = **Published**, Next Scene = scene 2) |

4. Click **Update Scene**.

**What you should see:** you are back on the **Scene Library** page. On the Unheard card, **Animation** now shows your video's title.

---

## Step 3: Add a glowing hotspot

1. Click **◎ Hotspots** in the sidebar, or open **http://localhost/just-want-to-be-heard/admin/interactive-elements/index.php**.
2. You are on the **Interactive Elements** page. Click **+ Create New Interactive Element**.
3. You are on the **Create Interactive Element** page. First, at the top:

| Field | What to do |
|---|---|
| **Scene** | Required. Pick **Unheard** (scene 1). Do this first, because it loads your video into the preview further down |
| **Element Name** | Required. A name only you see in the admin, for example `Riley's phone` |
| **Element Type** | Pick one: **Look closer (insight)** = a general explanation, **Riley's thought** = Riley's inner voice, or **Red flag (counts toward the red-flag score)** = a warning sign the viewer should notice |
| **Interaction Behavior** | Leave it as `info_popup` |
| **Character Association**, **Object Association** | Leave as **None** |
| **Label** | Optional. The short text shown next to the glowing spot, for example `Phone`. You can leave it empty |
| **Order** | Leave it at `0` |

4. Write the pop-up that opens when the viewer clicks the hotspot:

| Field | What to do |
|---|---|
| **Pop-up Title** | The heading of the card, for example `Nobody looks up` |
| **Pop-up Text (what the viewer learns when they click)** | One to three sentences, for example `Riley's parents are on their phones. Riley is right there, but nobody notices him.` |

5. Now place the hotspot. Scroll down to **Place the Hotspot on the Frame**. You should see your video there.
   1. Drag the **slider** under the video to the moment where the object is visible. The time (for example `4.5s`) shows next to the slider.
   2. With your mouse, **click and drag a box** over the object on the video frame. A box appears.
   3. Look back up: **X (px)**, **Y (px)**, **Width** and **Height** filled in by themselves. Don't type in them.
6. Decide **when** the hotspot appears, in the **Appears At (seconds)** field:
   - **Leave it empty** to show the hotspot when the scene ends (easiest), **or**
   - click **Use this time for "Appears At"** to show it at the moment you chose with the slider.
7. Leave these two boxes ticked:
   - **Pause the animation when this hotspot appears (recommended)**
   - **Element is active**
8. Click **Save Interactive Element**.

**What you should see:** you are back on the **Interactive Elements** page, and your hotspot is in the list with **Scene** showing Unheard.

> **Note about the demo hotspots.** Scene 1 already has three demo hotspots: **Family photo**, **Phone** and **School bag**. They were placed for the placeholder video, so on your video they will sit in the wrong places. Fix each one: click its **Edit**, then either draw its box again on your video or untick **Element is active** to hide it, and save.

---

## Step 4: Test it in the player

1. In the sidebar, click **▷ Play the Story**. The player opens in a new tab. (The address is **http://localhost/just-want-to-be-heard/viewer/scene.php?new=1**, which starts the story from the beginning.)
2. Press play. Your animation plays full screen.
3. At the time you chose, or when the scene ends if **Appears At** was empty, the video pauses and a **glowing spot** appears on your object.
4. Click the glowing spot. A **pop-up card** opens with your **Pop-up Title** and **Pop-up Text**. If you chose **Red flag**, the "Red flags found" counter at the top goes up.
5. Close the card and continue. The story moves on to scene 2.

**If something looks wrong:**

| Problem | Fix |
|---|---|
| Your old (demo) video still plays | Press **Ctrl + F5** to reload without the cache. Then check Step 2 saved the right **Assigned Animation** |
| The glowing spot is in the wrong place | Your video is probably not 1920 × 1080. Export it at 1920 × 1080, upload it again (Step 1 and Step 2), then **Edit** the hotspot and redraw its box |
| No hotspot appears | Edit the hotspot and check **Scene** = Unheard and **Element is active** is ticked |
| The preview in Step 3 says "Pick a scene that has a video to place the hotspot visually." | Pick the **Scene** first. If it still shows this, Step 2 was not saved |

---

## Doing more later

- **More hotspots:** repeat Step 3. One scene can have several.
- **Other scenes:** repeat Steps 1 to 3 and pick a different scene.
- **Reflection questions, story choices, endings:** see `INTERACTIVITY-GUIDE.md`.
