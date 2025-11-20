// Animasi background satu warna dengan efek gelombang
const canvas = document.createElement('canvas');
canvas.id = 'kas-animated-bg';
canvas.style.position = 'fixed';
canvas.style.top = '0';
canvas.style.left = '0';
canvas.style.width = '100vw';
canvas.style.height = '100vh';
canvas.style.zIndex = '-1';
canvas.style.pointerEvents = 'none';
document.body.prepend(canvas);

function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
resizeCanvas();
window.addEventListener('resize', resizeCanvas);

const ctx = canvas.getContext('2d');
let t = 0;
function drawWave() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    // Warna utama
    ctx.fillStyle = '#145da0';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    // Animasi gelombang
    ctx.save();
    ctx.globalAlpha = 0.15;
    for (let i = 0; i < 3; i++) {
        ctx.beginPath();
        for (let x = 0; x <= canvas.width; x += 10) {
            let y = 100 + 30 * Math.sin((x / 200) + t + i) + 20 * Math.cos((x / 100) - t * 0.7 + i * 2);
            if (x === 0) ctx.moveTo(x, y + i * 80);
            else ctx.lineTo(x, y + i * 80);
        }
        ctx.lineTo(canvas.width, canvas.height);
        ctx.lineTo(0, canvas.height);
        ctx.closePath();
        ctx.fillStyle = ['#fff', '#48cae4', '#0077b6'][i];
        ctx.fill();
    }
    ctx.restore();
    t += 0.012;
    requestAnimationFrame(drawWave);
}
drawWave();
