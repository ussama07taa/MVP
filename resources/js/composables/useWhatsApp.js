/**
 * Smart WhatsApp share:
 * - Mobile: attach PDF file via native share (WhatsApp with file)
 * - Desktop: open wa.me with signed PDF link + pre-filled message
 */
export function useWhatsApp() {
  const normalizePhone = (phone) => {
    let digits = String(phone).replace(/\D/g, '');
    if (digits.startsWith('0')) {
      digits = '212' + digits.slice(1);
    } else if (!digits.startsWith('212') && digits.length === 9) {
      digits = '212' + digits;
    }
    return digits;
  };

  const absoluteUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    return `${window.location.origin}${path.startsWith('/') ? path : `/${path}`}`;
  };

  const buildDocumentMessage = ({ clientName, docType, reference, total, pdfPath, withLink = true }) => {
    const pdfUrl = absoluteUrl(pdfPath);
    let message =
      `Bonjour ${clientName},\n\n` +
      `Voici votre ${docType} *${reference}* d'un montant de *${total} DH*.`;

    if (withLink) {
      message += `\n\n📄 Télécharger le PDF :\n${pdfUrl}`;
    }

    message += '\n\nMerci pour votre confiance !';
    return message;
  };

  const openWhatsApp = ({ phone, message }) => {
    const normalized = normalizePhone(phone);
    const waUrl = `https://wa.me/${normalized}?text=${encodeURIComponent(message)}`;
    window.open(waUrl, '_blank');
  };

  const sharePdfFile = async ({ pdfUrl, reference, message }) => {
    if (!navigator.share || !navigator.canShare) {
      return false;
    }

    const response = await fetch(pdfUrl);
    if (!response.ok) {
      return false;
    }

    const blob = await response.blob();
    const safeName = String(reference).replace(/[^a-zA-Z0-9-_]/g, '_') || 'document';
    const file = new File([blob], `${safeName}.pdf`, { type: 'application/pdf' });
    const shareData = { files: [file], text: message, title: reference };

    if (!navigator.canShare(shareData)) {
      return false;
    }

    await navigator.share(shareData);
    return true;
  };

  const shareDocument = async ({ client, pdfPath, reference, total, type = 'invoice' }) => {
    if (!client?.phone) {
      return { ok: false, error: 'no_phone' };
    }

    const docType = type === 'quote' ? 'devis' : 'facture';
    const totalFormatted = Number(total).toFixed(2);
    const pdfUrl = absoluteUrl(pdfPath);

    const shortMessage = buildDocumentMessage({
      clientName: client.name,
      docType,
      reference,
      total: totalFormatted,
      pdfPath,
      withLink: false,
    });

    const fullMessage = buildDocumentMessage({
      clientName: client.name,
      docType,
      reference,
      total: totalFormatted,
      pdfPath,
      withLink: true,
    });

    try {
      const sharedAsFile = await sharePdfFile({
        pdfUrl,
        reference,
        message: shortMessage,
      });

      if (sharedAsFile) {
        return { ok: true, mode: 'file' };
      }
    } catch (e) {
      if (e?.name === 'AbortError') {
        return { ok: false, error: 'cancelled' };
      }
    }

    openWhatsApp({ phone: client.phone, message: fullMessage });
    return { ok: true, mode: 'link' };
  };

  return { shareDocument, absoluteUrl, normalizePhone, buildDocumentMessage };
}
