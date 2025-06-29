// logout-after-inactive.js
import { getAuth, signOut, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";

const MAX_IDLE_MS = 60 * 60 * 1000; // 1 час

function updateLastActive() {
  localStorage.setItem("lastActive", Date.now());
}
window.addEventListener("mousemove", updateLastActive);
window.addEventListener("keydown", updateLastActive);
window.addEventListener("click", updateLastActive);

onAuthStateChanged(getAuth(), user => {
  if (!user) return;
  setInterval(() => {
    const last = Number(localStorage.getItem("lastActive") || 0);
    if (Date.now() - last > MAX_IDLE_MS) {
      signOut(getAuth()).then(() => {
        window.location.href = "login.html";
      });
    }
  }, 60 * 1000); // Проверять каждую минуту
  updateLastActive();
});