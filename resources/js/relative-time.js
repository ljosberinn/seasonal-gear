const units = [
    ['year', 365 * 24 * 60 * 60],
    ['month', 30 * 24 * 60 * 60],
    ['day', 24 * 60 * 60],
    ['hour', 60 * 60],
    ['minute', 60],
];

function format(date) {
    const seconds = (date.getTime() - Date.now()) / 1000;
    // the label around it is English, so the phrase is too; only the tooltip follows the viewer's locale
    const formatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return formatter.format(Math.round(seconds / size), unit);
        }
    }

    return 'just now';
}

// Server renders UTC; rewrite in the viewer's locale and time zone and keep it current.
export function initRelativeTimes() {
    const elements = document.querySelectorAll('time[data-relative-time]');

    const update = () => {
        for (const element of elements) {
            const date = new Date(element.dateTime);

            element.textContent = `${element.dataset.relativeTime} ${format(date)}`;
            element.title = date.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
        }
    };

    if (elements.length > 0) {
        update();
        window.setInterval(update, 60_000);
    }
}
