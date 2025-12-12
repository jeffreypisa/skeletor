import MatchHeight from 'matchheight';

let matchHeightInstance = null;
let hasBoundTabListener = false;

export function matchheightInit() {
        // Controleer of MatchHeight beschikbaar is
        if (typeof MatchHeight !== 'function') {
                console.error('MatchHeight library is not loaded.');
                return;
        }

        // Stop een bestaande instantie en initialiseer opnieuw
        if (matchHeightInstance?.disconnect) {
                matchHeightInstance.disconnect();
        }

        matchHeightInstance = new MatchHeight();
        matchheightUpdate();

        // Stel de waarde voor `--vh` in
        const setViewportHeight = () => {
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
        };

        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);

        // Forceer een nieuwe hoogteberekening wanneer tabbladen wisselen
        if (!hasBoundTabListener) {
                document.addEventListener('shown.bs.tab', () => {
                        requestAnimationFrame(matchheightUpdate);
                });
                hasBoundTabListener = true;
        }
}

export function matchheightUpdate() {
        if (matchHeightInstance && typeof matchHeightInstance.update === 'function') {
                matchHeightInstance.update();
        }
}
