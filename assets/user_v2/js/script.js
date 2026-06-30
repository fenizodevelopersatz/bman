// Banner Slider
// const slides = document.querySelectorAll(".slide");
// const dotsBox = document.getElementById("sliderDots");
// let current = 0;

// slides.forEach((_, i) => {
//   const d = document.createElement("div");
//   d.className = `dot ${i === 0 ? "active" : ""}`;
//   d.onclick = () => showSlide(i);
//   dotsBox.appendChild(d);
// });

// function showSlide(idx) {
//   slides[current].classList.remove("active");
//   dotsBox.children[current].classList.remove("active");
//   current = idx;
//   slides[current].classList.add("active");
//   dotsBox.children[current].classList.add("active");
// }

// setInterval(() => {
//   showSlide((current + 1) % slides.length);
// }, 5000);

function copyRef() {
  const input = document.querySelector('input[readonly]');
  input.select();
  input.setSelectionRange(0, 99999);
  document.execCommand("copy");
  alert("Referral link copied!");
}