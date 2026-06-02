export function usePrint() {
  const printOrder = () => {
    // Get the rendered invoice element
    const el = document.getElementById('print-area');

    if (!el) {
      // Fallback: just window.print() — something went wrong
      window.print();
      return;
    }

    // Clone the invoice HTML so we can inject it into a clean popup window
    const printHtml = el.innerHTML;

    // Grab all <link rel="stylesheet"> and <style> tags from the main document
    const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
      .map(node => node.outerHTML)
      .join('\n');

    // Open a blank popup
    const popup = window.open('', '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');

    if (!popup) {
      // Popup was blocked — fall back to window.print()
      window.print();
      return;
    }

    popup.document.write(`
      <!DOCTYPE html>
      <html lang="fr">
        <head>
          <meta charset="UTF-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1.0" />
          <title>Facture</title>
          ${styles}
          <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { margin: 0; font-family: 'Inter', sans-serif; background: white; }
            @page { size: A4; margin: 0; }
            @media print {
              html, body { width: 210mm; height: 297mm; }
            }
          </style>
        </head>
        <body>
          <div style="background:white; min-height: 297mm; font-family: 'Inter', sans-serif;">
            ${printHtml}
          </div>
        </body>
      </html>
    `);

    popup.document.close();

    // Wait for styles to load, then print
    popup.onload = () => {
      setTimeout(() => {
        popup.focus();
        popup.print();
        // Close popup after print dialog closes
        // popup.close(); // optional — comment out to let user keep window
      }, 600);
    };

    // Fallback if onload doesn't fire (common in Chrome)
    setTimeout(() => {
      try {
        popup.focus();
        popup.print();
      } catch(e) {
        // already printed
      }
    }, 1000);
  };

  return { printOrder };
}
