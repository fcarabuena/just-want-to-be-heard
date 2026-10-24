-- Demo story for trying the interactive player.
-- Import AFTER schema.sql into an EMPTY database (it uses scene numbers 1-6).
-- The videos are placeholders in uploads/videos/demo/. Replace them with the real
-- animation by editing each Video in the admin, and adjust the hotspot boxes.

USE `just_want_to_be_heard`;

INSERT INTO videos (title, filename, file_path, mime_type, file_size, resolution, is_published) VALUES
('Demo 1 - Unheard', '01-unheard.mp4', '/uploads/videos/demo/01-unheard.mp4', 'video/mp4', 0, '1920x1080', 1),
('Demo 2 - Someone Who Listens', '02-someone-listens.mp4', '/uploads/videos/demo/02-someone-listens.mp4', 'video/mp4', 0, '1920x1080', 1),
('Demo 3 - Let''s Meet Up', '03-lets-meet-up.mp4', '/uploads/videos/demo/03-lets-meet-up.mp4', 'video/mp4', 0, '1920x1080', 1),
('Demo 4 - The Park', '04-the-park.mp4', '/uploads/videos/demo/04-the-park.mp4', 'video/mp4', 0, '1920x1080', 1),
('Demo 5 - Heard', '05-heard.mp4', '/uploads/videos/demo/05-heard.mp4', 'video/mp4', 0, '1920x1080', 1),
('Demo 6 - Silence', '06-silence.mp4', '/uploads/videos/demo/06-silence.mp4', 'video/mp4', 0, '1920x1080', 1);

INSERT INTO scenes (scene_number, title, slug, description, video_id, is_interactive, ending_type, choice_prompt, status, display_order) VALUES
(1, 'Unheard', 'unheard', 'Riley tries to talk to his parents, but they wave him away.', (SELECT video_id FROM videos WHERE filename = '01-unheard.mp4'), 1, 'none', NULL, 'published', 1),
(2, 'Someone Who Listens', 'someone-who-listens', 'Late at night, an online friend always replies.', (SELECT video_id FROM videos WHERE filename = '02-someone-listens.mp4'), 1, 'none', NULL, 'published', 2),
(3, 'Let''s Meet Up', 'lets-meet-up', 'After the fight, Riley runs to a computer shop. His online friend wants to meet.', (SELECT video_id FROM videos WHERE filename = '03-lets-meet-up.mp4'), 1, 'none', 'Riley''s online friend wants to meet tonight. What should Riley do?', 'published', 3),
(4, 'The Park', 'the-park', 'The "friend" turns out to be a grown man.', (SELECT video_id FROM videos WHERE filename = '04-the-park.mp4'), 1, 'none', 'Riley is scared. What should he do?', 'published', 4),
(5, 'Heard', 'heard', 'Riley finds help, and his parents finally listen.', (SELECT video_id FROM videos WHERE filename = '05-heard.mp4'), 1, 'good', NULL, 'published', 5),
(6, 'Silence', 'silence', 'Riley keeps everything to himself.', (SELECT video_id FROM videos WHERE filename = '06-silence.mp4'), 1, 'bad', NULL, 'published', 6);

SET @s1 = (SELECT scene_id FROM scenes WHERE slug = 'unheard');
SET @s2 = (SELECT scene_id FROM scenes WHERE slug = 'someone-who-listens');
SET @s3 = (SELECT scene_id FROM scenes WHERE slug = 'lets-meet-up');
SET @s4 = (SELECT scene_id FROM scenes WHERE slug = 'the-park');
SET @s5 = (SELECT scene_id FROM scenes WHERE slug = 'heard');
SET @s6 = (SELECT scene_id FROM scenes WHERE slug = 'silence');

UPDATE scenes SET next_scene_id = @s2 WHERE scene_id = @s1;
UPDATE scenes SET next_scene_id = @s3 WHERE scene_id = @s2;

-- Hotspots. Positions are in 1920x1080 frame pixels. Empty appear_at = shown when the scene ends.
INSERT INTO interactive_elements (scene_id, element_name, element_type, label, insight_title, insight_text, position_x, position_y, width, height, appear_at_seconds, pauses_video, display_order) VALUES
(@s1, 'Family photo', 'thought', 'Family photo', '"They used to listen to me."', 'Riley remembers when his family laughed together at dinner. Now every conversation turns into an argument, and he feels invisible at home.', 260, 330, 320, 240, NULL, 1, 1),
(@s1, 'Phone', 'thought', 'Phone', '"At least someone online answers."', 'When home feels lonely, it is natural to look for people who listen. That need is real and not a weakness. It is also exactly the gap that people with bad intentions look for.', 1340, 640, 300, 200, NULL, 1, 2),
(@s1, 'School bag', 'thought', 'School bag', '"I can''t do anything right."', 'Grades, pressure, and feeling misunderstood can pile up. Talking to a teacher or guidance counselor is one way to be heard when home feels hard.', 780, 700, 330, 220, NULL, 1, 3),

(@s2, 'Flattery', 'red_flag', 'You''re so mature for your age', 'Red flag: Flattery', 'Telling a young person they are "mature for their age" makes them feel special and older than they are. Groomers use it to make an adult relationship with a minor seem normal.', 560, 260, 800, 90, 3.0, 1, 1),
(@s2, 'Normal message', 'insight', 'haha same, I love that show', 'This one is normal', 'Not every friendly message is a red flag. Grooming is a pattern that builds over time, which is why it is important to notice the other messages too.', 560, 380, 800, 90, 3.0, 1, 2),
(@s2, 'Isolation', 'red_flag', 'Your parents don''t get you like I do', 'Red flag: Isolation', 'Turning you against your family or friends makes you depend on one person. Someone who truly cares about you would not try to pull you away from the people around you.', 560, 500, 800, 90, 3.0, 1, 3),
(@s2, 'Secrecy', 'red_flag', 'Let''s keep this our secret', 'Red flag: Secrecy', 'Safe people do not ask you to hide a relationship from the adults who care about you. Asking for secrets is one of the clearest warning signs of grooming.', 560, 620, 800, 90, 3.0, 1, 4),
(@s2, 'Hiding evidence', 'red_flag', 'Delete our chats after', 'Red flag: Hiding evidence', 'Asking you to delete messages protects the other person, not you. If someone asks this, save the chats and show a trusted adult.', 560, 740, 800, 90, 3.0, 1, 5),

(@s3, 'Meet-up request', 'red_flag', 'Let''s meet up tonight', 'Red flag: Meeting alone', 'An online friend asking to meet in person, alone, at night, and without your parents knowing is a serious warning sign. If you ever want to meet someone from online, a trusted adult should know and come with you.', 660, 420, 600, 160, NULL, 1, 1),

(@s4, 'Police officer', 'insight', 'Police officer', 'Look for safe adults', 'If you feel unsafe in public, go to a police officer, a security guard, a store employee, or a parent with children. It is always okay to ask for help.', 1450, 380, 200, 420, NULL, 1, 1);

INSERT INTO reflection_questions (scene_id, question_text, question_type, options_text, display_order) VALUES
(@s1, 'How do you think Riley feels after his parents send him away?', 'multiple_choice', 'Lonely || Feeling unheard at home can make anyone feel alone. Riley deserves to be listened to.\nAngry || Anger often hides hurt. Riley wants his parents to notice him.\nFine, he''s used to it || Getting "used to it" can mean someone has stopped expecting to be heard, which is a heavy feeling to carry.', 1),
(@s2, 'Which of these messages worried you the most, and why?', 'open', NULL, 1),
(@s3, 'Riley''s online friend asks to meet tonight, alone. What do you notice?', 'multiple_choice', 'It feels exciting. Someone finally cares. || That feeling is understandable, and it is exactly what makes grooming hard to see. Excitement and danger can feel the same at first.\nThe secrecy and the timing feel wrong. || Good noticing. Meeting alone, at night, without your parents knowing are all warning signs.\nI am not sure. || That is okay. When you are not sure, talking to a trusted adult is always a safe next step.', 1);

INSERT INTO scene_choices (scene_id, choice_text, target_scene_id, feedback_text, display_order) VALUES
(@s3, 'Go meet his online friend', @s4, 'Riley logs off and heads to the park.', 1),
(@s3, 'Go home and tell Mom and Dad', @s5, 'Riley takes a deep breath and walks home.', 2),
(@s4, 'Run and ask the police officer for help', @s5, 'Riley runs toward the officer.', 1),
(@s4, 'Stay quiet and go home alone', @s6, 'Riley decides not to tell anyone.', 2);
