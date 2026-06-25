import QRCode from 'qrcode';

document.addEventListener('DOMContentLoaded', () => {
    const students = window.pkgQrPrintStudents || [];

    students.forEach(async (student) => {
        const container = document.getElementById(`qr-${student.id}`);

        if (!container) {
            return;
        }

        try {
            container.innerHTML = '';
            const canvas = document.createElement('canvas');
            await QRCode.toCanvas(canvas, JSON.stringify(student.qr_data), {
                width: 200,
                margin: 1,
                errorCorrectionLevel: 'L',
                color: {
                    dark: '#000000',
                    light: '#ffffff',
                },
            });
            container.appendChild(canvas);
        } catch (error) {
            console.error(`QR Error for student ${student.id}`, error);
            container.innerHTML = '<div class="qr-placeholder">Error</div>';
        }
    });
});
