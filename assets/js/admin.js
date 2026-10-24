document.addEventListener('DOMContentLoaded', function () {
    const navItems = document.querySelectorAll('.nav-item');

    navItems.forEach(function (item) {
        item.addEventListener('mouseenter', function () {
            item.style.transform = 'translateX(2px)';
        });

        item.addEventListener('mouseleave', function () {
            item.style.transform = 'translateX(0)';
        });
    });
});

// Hotspot placement: draw a box on the scene's video frame to fill X/Y/Width/Height (1920x1080 pixels).
document.addEventListener('DOMContentLoaded', function () {
    const tool = document.getElementById('placementTool');
    if (!tool) {
        return;
    }

    const FRAME_W = 1920;
    const FRAME_H = 1080;
    const videos = JSON.parse(tool.dataset.videos || '{}');
    const video = document.getElementById('placementVideo');
    const surface = document.getElementById('placementSurface');
    const box = document.getElementById('placementBox');
    const empty = document.getElementById('placementEmpty');
    const scrub = document.getElementById('placementScrub');
    const timeLabel = document.getElementById('placementTime');
    const sceneSelect = document.getElementById('scene_id');
    const fields = ['position_x', 'position_y', 'width', 'height'].map(function (id) { return document.getElementById(id); });
    const appearAt = document.getElementById('appear_at_seconds');

    function loadSceneVideo() {
        const src = videos[sceneSelect.value];
        tool.classList.toggle('has-video', Boolean(src));
        empty.hidden = Boolean(src);
        if (src && video.getAttribute('src') !== src) {
            video.setAttribute('src', src);
            video.addEventListener('loadedmetadata', function onMeta() {
                video.removeEventListener('loadedmetadata', onMeta);
                scrub.max = video.duration.toFixed(1);
                const t = parseFloat(appearAt.value);
                if (!isNaN(t)) {
                    video.currentTime = Math.min(t, video.duration);
                    scrub.value = t;
                    timeLabel.textContent = t.toFixed(1) + 's';
                }
            });
        }
    }

    function drawFromFields() {
        const v = fields.map(function (f) { return parseInt(f.value, 10); });
        if (v.some(isNaN)) {
            box.hidden = true;
            return;
        }
        box.hidden = false;
        box.style.left = (v[0] / FRAME_W * 100) + '%';
        box.style.top = (v[1] / FRAME_H * 100) + '%';
        box.style.width = (v[2] / FRAME_W * 100) + '%';
        box.style.height = (v[3] / FRAME_H * 100) + '%';
    }

    function toFrame(event) {
        const rect = surface.getBoundingClientRect();
        return {
            x: Math.max(0, Math.min(FRAME_W, (event.clientX - rect.left) / rect.width * FRAME_W)),
            y: Math.max(0, Math.min(FRAME_H, (event.clientY - rect.top) / rect.height * FRAME_H)),
        };
    }

    let start = null;
    surface.addEventListener('pointerdown', function (event) {
        start = toFrame(event);
        surface.setPointerCapture(event.pointerId);
    });
    surface.addEventListener('pointermove', function (event) {
        if (!start) {
            return;
        }
        const p = toFrame(event);
        fields[0].value = Math.round(Math.min(start.x, p.x));
        fields[1].value = Math.round(Math.min(start.y, p.y));
        fields[2].value = Math.round(Math.abs(p.x - start.x));
        fields[3].value = Math.round(Math.abs(p.y - start.y));
        drawFromFields();
    });
    surface.addEventListener('pointerup', function () { start = null; });

    scrub.addEventListener('input', function () {
        video.currentTime = parseFloat(scrub.value);
        timeLabel.textContent = parseFloat(scrub.value).toFixed(1) + 's';
    });

    document.getElementById('placementUseTime').addEventListener('click', function () {
        appearAt.value = parseFloat(scrub.value).toFixed(1);
    });

    fields.forEach(function (f) { f.addEventListener('input', drawFromFields); });
    sceneSelect.addEventListener('change', loadSceneVideo);
    loadSceneVideo();
    drawFromFields();
});
