const columns = ['id', 'name', 'slot'];

function csvField(value) {
    const text = String(value);

    return /[",\r\n]/.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
}

function serialize(rows, format) {
    if (format === 'csv') {
        const lines = [columns, ...rows.map((row) => columns.map((column) => row[column]))];

        return { type: 'text/csv', content: `${lines.map((line) => line.map(csvField).join(',')).join('\r\n')}\r\n` };
    }

    return { type: 'application/json', content: `${JSON.stringify(rows, null, 2)}\n` };
}

function download(filename, type, content) {
    const url = URL.createObjectURL(new Blob([content], { type }));
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;
    link.click();
    // revoking in the same task can cancel the download in some browsers
    window.setTimeout(() => URL.revokeObjectURL(url));
}

export function initExport() {
    for (const exporter of document.querySelectorAll('[data-export]')) {
        const rows = JSON.parse(exporter.querySelector('[data-export-rows]').textContent);

        exporter.addEventListener('click', (event) => {
            const format = event.target.closest('[data-export-format]')?.dataset.exportFormat;

            if (format) {
                const { type, content } = serialize(rows, format);
                download(`${exporter.dataset.export}.${format}`, type, content);
            }
        });
    }
}
