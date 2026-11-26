// Hover cards modelled on gerritalex.de's AlbumMosaic: mice open on hover, touch opens on the first tap
// instead of following the trigger link, keyboards open on focus.
const closeDelay = 150;

let open = null;
let closeTimer;

function show(flyout) {
    window.clearTimeout(closeTimer);

    if (open === flyout) {
        return;
    }

    hide();

    const panel = flyout.querySelector('[data-flyout-panel]');
    const container = flyout.closest('[data-flyout-container]') ?? document.body;

    panel.hidden = false;
    // flip to the trigger's right edge when the panel would stick out past the grid
    const overflows = flyout.getBoundingClientRect().left + panel.offsetWidth > container.getBoundingClientRect().right;
    panel.classList.toggle('left-0', !overflows);
    panel.classList.toggle('right-0', overflows);

    flyout.classList.add('z-20');
    flyout.querySelector('[data-flyout-trigger]').setAttribute('aria-expanded', 'true');
    open = flyout;
}

function hide() {
    window.clearTimeout(closeTimer);

    if (open === null) {
        return;
    }

    open.querySelector('[data-flyout-panel]').hidden = true;
    open.classList.remove('z-20');
    open.querySelector('[data-flyout-trigger]').setAttribute('aria-expanded', 'false');
    open = null;
}

export function initFlyouts() {
    const flyouts = [...document.querySelectorAll('[data-flyout]')].filter((flyout) => flyout.querySelector('[data-flyout-panel]'));

    if (flyouts.length === 0) {
        return;
    }

    for (const flyout of flyouts) {
        const trigger = flyout.querySelector('[data-flyout-trigger]');
        let pointerType = '';
        let wasOpen = false;

        flyout.addEventListener('pointerenter', (event) => {
            if (event.pointerType === 'mouse') {
                show(flyout);
            }
        });

        flyout.addEventListener('pointerleave', (event) => {
            if (event.pointerType === 'mouse' && open === flyout) {
                window.clearTimeout(closeTimer);
                closeTimer = window.setTimeout(hide, closeDelay);
            }
        });

        trigger.addEventListener('pointerdown', (event) => {
            pointerType = event.pointerType;
            // a tap may focus the link and open the flyout before `click` runs
            wasOpen = open === flyout;
        });

        trigger.addEventListener('click', (event) => {
            if (pointerType !== 'mouse' && event.detail !== 0 && !wasOpen) {
                event.preventDefault();
                show(flyout);
            }
        });

        flyout.addEventListener('focusin', () => show(flyout));

        flyout.addEventListener('focusout', (event) => {
            if (open === flyout && !flyout.contains(event.relatedTarget)) {
                hide();
            }
        });
    }

    document.addEventListener('pointerdown', (event) => {
        if (open !== null && !open.contains(event.target)) {
            hide();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && open !== null) {
            const trigger = open.querySelector('[data-flyout-trigger]');
            const hadFocus = open.contains(document.activeElement);

            hide();

            if (hadFocus) {
                trigger.focus({ preventScroll: true });
                // focusing the trigger reopens it via focusin
                hide();
            }
        }
    });

    let width = window.innerWidth;

    // mobile toolbars collapsing only change the height
    window.addEventListener('resize', () => {
        if (window.innerWidth !== width) {
            width = window.innerWidth;
            hide();
        }
    });
}
