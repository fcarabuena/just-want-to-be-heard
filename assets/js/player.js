(function () {
    'use strict';

    const STORY = window.STORY;
    const FRAME_W = 1920;
    const FRAME_H = 1080;
    const JOURNEY_KEY = 'jwtbh-journey';

    const $ = (id) => document.getElementById(id);
    const video = $('video');
    const stage = $('stage');
    const hotspotLayer = $('hotspotLayer');

    const TAGS = { red_flag: 'Red flag', thought: "Riley's thoughts", insight: 'Look closer' };

    const ENDINGS = {
        good: {
            tag: 'Good Ending',
            title: 'Riley was heard',
            text: 'Riley spoke up, and the people who care about him listened. Telling a trusted adult takes courage, and it is never too late to do it.',
        },
        bad: {
            tag: 'Bad Ending',
            title: "This is not Riley's fault",
            text: 'The adult who manipulated Riley is the only one responsible. Grooming is designed to make young people feel trapped, ashamed, and alone. In real life, it is never too late to tell someone you trust and get help.',
        },
        neutral: {
            tag: 'The End',
            title: 'Thank you for listening',
            text: 'Everyone deserves to be heard. If something online ever feels wrong, talk to an adult you trust.',
        },
    };

    let current = null;
    let firedTimed = new Set();
    let exploring = false;
    let resumeAfterOverlay = false;

    // ---------- journey (kept only in this browser tab) ----------

    function emptyJourney() {
        return { choices: [], flags: [], reflections: [], lastChoiceScene: null };
    }

    function loadJourney() {
        try {
            return JSON.parse(sessionStorage.getItem(JOURNEY_KEY)) || emptyJourney();
        } catch (e) {
            return emptyJourney();
        }
    }

    let journey = STORY.fresh ? emptyJourney() : loadJourney();

    function saveJourney() {
        try {
            sessionStorage.setItem(JOURNEY_KEY, JSON.stringify(journey));
        } catch (e) { /* private mode: the journey just will not survive a reload */ }
    }

    // ---------- helpers ----------

    function show(el) { el.hidden = false; }
    function hide(el) { el.hidden = true; }

    function allRedFlagIds() {
        const ids = [];
        Object.values(STORY.scenes).forEach((scene) => {
            scene.hotspots.forEach((h) => { if (h.type === 'red_flag') ids.push(h.id); });
        });
        return ids;
    }

    function pauseForOverlay() {
        resumeAfterOverlay = !video.paused && !video.ended;
        video.pause();
    }

    function resumeFromOverlay() {
        if (resumeAfterOverlay && !exploring) video.play().catch(() => {});
        resumeAfterOverlay = false;
    }

    // ---------- scenes ----------

    function playScene(id, options) {
        const opts = options || {};
        current = STORY.scenes[id];
        if (!current) { showEnding('neutral'); return; }

        firedTimed = new Set();
        exploring = false;
        hotspotLayer.innerHTML = '';
        ['exploreBar', 'insightOverlay', 'reflectionOverlay', 'choiceOverlay', 'endingOverlay'].forEach((el) => hide($(el)));
        updateRedFlagCounter();

        stage.classList.add('fade-out');
        setTimeout(() => {
            video.src = current.video;
            video.load();
            if (opts.toChoices) {
                video.addEventListener('loadedmetadata', function jump() {
                    video.removeEventListener('loadedmetadata', jump);
                    video.currentTime = Math.max(0, video.duration - 0.05);
                    current.hotspots.forEach((h) => firedTimed.add(h.id));
                    stage.classList.remove('fade-out');
                    showChoices();
                });
            } else {
                video.play().catch(() => {});
                showSceneTitle();
                stage.classList.remove('fade-out');
            }
        }, 450);
    }

    function showSceneTitle() {
        if (!STORY.showTitle && !STORY.showNumber) return;
        const el = $('sceneTitle');
        const parts = [];
        if (STORY.showNumber) parts.push('Scene ' + current.number);
        if (STORY.showTitle) parts.push(current.title);
        el.textContent = parts.join(' · ');
        el.classList.remove('visible');
        void el.offsetWidth;
        el.classList.add('visible');
    }

    video.addEventListener('timeupdate', () => {
        if (!current || exploring) return;
        const due = current.hotspots.filter((h) => h.at !== null && h.at <= video.currentTime && !firedTimed.has(h.id));
        if (!due.length) return;
        due.forEach((h) => firedTimed.add(h.id));
        if (due.some((h) => h.pauses)) {
            video.pause();
            enterExplore(due, true);
        } else {
            due.forEach(renderHotspot);
        }
    });

    video.addEventListener('ended', () => {
        if (!current) return;
        const endSpots = current.hotspots.filter((h) => h.at === null);
        if (endSpots.length) {
            enterExplore(endSpots, false);
        } else {
            afterScene();
        }
    });

    // ---------- hotspots ----------

    function renderHotspot(h) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'hotspot hotspot-' + h.type;
        if (journey.flags.includes(h.id)) btn.classList.add('visited');
        btn.style.left = (h.x / FRAME_W * 100) + '%';
        btn.style.top = (h.y / FRAME_H * 100) + '%';
        btn.style.width = (h.w / FRAME_W * 100) + '%';
        btn.style.height = (h.h / FRAME_H * 100) + '%';
        btn.dataset.id = h.id;
        btn.setAttribute('aria-label', h.label);
        btn.innerHTML = '<span class="hotspot-ring"></span><span class="hotspot-label"></span>';
        btn.querySelector('.hotspot-label').textContent = h.label;
        btn.addEventListener('click', () => openInsight(h, btn));
        hotspotLayer.appendChild(btn);
    }

    function enterExplore(spots, resumeAfter) {
        exploring = true;
        spots.forEach(renderHotspot);
        const hasFlags = spots.some((h) => h.type === 'red_flag');
        $('exploreHint').textContent = hasFlags
            ? 'Something feels off. Tap the messages that look like red flags.'
            : 'Tap the glowing spots to look closer.';
        show($('exploreBar'));
        $('exploreContinue').onclick = () => {
            hide($('exploreBar'));
            spots.forEach((h) => {
                const el = hotspotLayer.querySelector('[data-id="' + h.id + '"]');
                if (el) el.remove();
            });
            exploring = false;
            if (resumeAfter) {
                video.play().catch(() => {});
            } else {
                afterScene();
            }
        };
    }

    function openInsight(h, btn) {
        pauseForOverlay();
        btn.classList.add('visited');
        $('insightTag').textContent = TAGS[h.type] || TAGS.insight;
        $('insightTag').className = 'tag tag-' + h.type;
        $('insightTitle').textContent = h.title;
        $('insightText').textContent = h.text;
        if (h.type === 'red_flag' && !journey.flags.includes(h.id)) {
            journey.flags.push(h.id);
            saveJourney();
            updateRedFlagCounter();
        }
        show($('insightOverlay'));
        $('insightClose').focus();
    }

    function closeInsight() {
        hide($('insightOverlay'));
        resumeFromOverlay();
    }

    function updateRedFlagCounter() {
        const sceneFlags = current ? current.hotspots.filter((h) => h.type === 'red_flag') : [];
        if (!sceneFlags.length) { hide($('redflagCounter')); return; }
        $('redflagTotal').textContent = sceneFlags.length;
        $('redflagFound').textContent = sceneFlags.filter((h) => journey.flags.includes(h.id)).length;
        show($('redflagCounter'));
    }

    // ---------- reflection questions ----------

    function afterScene() {
        hotspotLayer.innerHTML = '';
        runReflections(current.reflections.slice(), decideNext);
    }

    function runReflections(queue, done) {
        const q = queue.shift();
        if (!q) { hide($('reflectionOverlay')); done(); return; }

        $('reflectionQuestion').textContent = q.question;
        const list = $('reflectionOptions');
        const answer = $('reflectionAnswer');
        const feedback = $('reflectionFeedback');
        const next = $('reflectionNext');
        list.innerHTML = '';
        hide(feedback);
        answer.value = '';

        if (q.type === 'open') {
            show(answer);
            show(next);
        } else {
            hide(answer);
            hide(next);
            q.options.forEach((opt) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'option';
                b.textContent = opt.text;
                b.addEventListener('click', () => {
                    list.querySelectorAll('.option').forEach((o) => { o.disabled = true; });
                    b.classList.add('chosen');
                    feedback.textContent = opt.feedback || 'Thank you for thinking about this.';
                    show(feedback);
                    show(next);
                    journey.reflections.push({ question: q.question, answer: opt.text });
                    saveJourney();
                    next.focus();
                });
                list.appendChild(b);
            });
        }

        next.onclick = () => {
            if (q.type === 'open' && answer.value.trim() !== '') {
                journey.reflections.push({ question: q.question, answer: answer.value.trim() });
                saveJourney();
            }
            runReflections(queue, done);
        };
        show($('reflectionOverlay'));
    }

    // ---------- choices and flow ----------

    function linearNext() {
        if (current.next && STORY.scenes[current.next]) return current.next;
        const idx = STORY.order.indexOf(current.id);
        return idx >= 0 && idx + 1 < STORY.order.length ? STORY.order[idx + 1] : null;
    }

    function decideNext() {
        if (current.choices.length) { showChoices(); return; }
        if (current.ending !== 'none') { showEnding(current.ending); return; }
        const next = linearNext();
        if (next) playScene(next); else showEnding('neutral');
    }

    function showChoices() {
        $('choicePrompt').textContent = current.choicePrompt;
        const list = $('choiceList');
        const feedback = $('choiceFeedback');
        list.innerHTML = '';
        hide(feedback);
        current.choices.forEach((choice) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'choice';
            b.textContent = choice.text;
            b.addEventListener('click', () => {
                list.querySelectorAll('.choice').forEach((c) => { c.disabled = true; c.classList.add('dim'); });
                b.classList.remove('dim');
                b.classList.add('chosen');
                journey.choices.push({ scene: current.id, sceneTitle: current.title, text: choice.text });
                journey.lastChoiceScene = current.id;
                saveJourney();
                const go = () => {
                    hide($('choiceOverlay'));
                    if (choice.target && STORY.scenes[choice.target]) {
                        playScene(choice.target);
                    } else if (current.ending !== 'none') {
                        showEnding(current.ending);
                    } else {
                        const next = linearNext();
                        if (next) playScene(next); else showEnding('neutral');
                    }
                };
                if (choice.feedback) {
                    feedback.textContent = choice.feedback;
                    show(feedback);
                    setTimeout(go, 2600);
                } else {
                    setTimeout(go, 700);
                }
            });
            list.appendChild(b);
        });
        show($('choiceOverlay'));
        const first = list.querySelector('.choice');
        if (first) first.focus();
    }

    function showEnding(type) {
        const ending = ENDINGS[type] || ENDINGS.neutral;
        $('endingTag').textContent = ending.tag;
        $('endingTag').className = 'tag tag-ending-' + type;
        $('endingTitle').textContent = ending.title;
        $('endingText').textContent = ending.text;

        const list = $('journeyList');
        list.innerHTML = '';
        if (!journey.choices.length) {
            const li = document.createElement('li');
            li.textContent = 'You watched the story without making a choice.';
            list.appendChild(li);
        }
        journey.choices.forEach((c) => {
            const li = document.createElement('li');
            li.innerHTML = '<span class="journey-scene"></span> <span class="journey-choice"></span>';
            li.querySelector('.journey-scene').textContent = c.sceneTitle + ':';
            li.querySelector('.journey-choice').textContent = c.text;
            list.appendChild(li);
        });

        const totalFlags = allRedFlagIds().length;
        $('journeyFlags').textContent = totalFlags
            ? 'You spotted ' + journey.flags.filter((id) => allRedFlagIds().includes(id)).length + ' of ' + totalFlags + ' red flags.'
            : '';

        $('rewindButton').hidden = !journey.lastChoiceScene;
        hide($('redflagCounter'));
        show($('endingOverlay'));
    }

    $('rewindButton').addEventListener('click', () => {
        const sceneId = journey.lastChoiceScene;
        journey.choices.pop();
        const prev = journey.choices[journey.choices.length - 1];
        journey.lastChoiceScene = prev ? prev.scene : null;
        saveJourney();
        playScene(sceneId, { toChoices: true });
    });

    $('restartButton').addEventListener('click', () => {
        journey = emptyJourney();
        saveJourney();
        playScene(STORY.start);
    });

    // ---------- controls ----------

    $('startButton').addEventListener('click', () => {
        hide($('startOverlay'));
        saveJourney();
        playScene(STORY.initial);
    });
    if (journey.choices.length && !STORY.fresh) $('startButton').textContent = 'Continue';

    $('insightClose').addEventListener('click', closeInsight);
    $('insightOverlay').addEventListener('click', (e) => { if (e.target === e.currentTarget) closeInsight(); });

    const playPause = $('playPause');
    playPause.addEventListener('click', () => {
        if (exploring || !$('choiceOverlay').hidden || !$('reflectionOverlay').hidden) return;
        if (video.paused) video.play().catch(() => {}); else video.pause();
    });
    video.addEventListener('play', () => { playPause.textContent = '❚❚'; playPause.setAttribute('aria-label', 'Pause'); });
    video.addEventListener('pause', () => { playPause.textContent = '▶'; playPause.setAttribute('aria-label', 'Play'); });

    const muteToggle = $('muteToggle');
    muteToggle.addEventListener('click', () => {
        video.muted = !video.muted;
        muteToggle.textContent = video.muted ? '🔇' : '🔊';
        muteToggle.setAttribute('aria-label', video.muted ? 'Unmute' : 'Mute');
    });

    const fullscreenToggle = $('fullscreenToggle');
    if (fullscreenToggle) {
        fullscreenToggle.addEventListener('click', () => {
            if (document.fullscreenElement) document.exitFullscreen();
            else $('player').requestFullscreen().catch(() => {});
        });
    }

    $('helpButton').addEventListener('click', () => { pauseForOverlay(); show($('helpPanel')); $('helpClose').focus(); });
    $('helpClose').addEventListener('click', () => { hide($('helpPanel')); resumeFromOverlay(); });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (!$('helpPanel').hidden) { hide($('helpPanel')); resumeFromOverlay(); }
        else if (!$('insightOverlay').hidden) closeInsight();
    });
})();
