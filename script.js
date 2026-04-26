/**
 * ==========================================
 * ส่วนที่ 3 & 4: ระบบ Welcome Slider (แบนเนอร์หลัก)
 * ==========================================
 */

let currentIndex = 0; 
let autoSlideInterval; 
let touchStartX = 0; 
let touchEndX = 0; 

function handleSwipe() {
    const swipeDistance = touchEndX - touchStartX;
    const minSwipeDistance = 50; 

    if (swipeDistance < -minSwipeDistance) {
        moveSlide('main', 1);
    } else if (swipeDistance > minSwipeDistance) {
        moveSlide('main', -1);
    }
}

function moveSlide(sliderName, direction) {
    const track = document.getElementById('main-track');
    const totalSlides = document.querySelectorAll('#main-track .slide').length; 

    if (!track || totalSlides === 0) return; 

    currentIndex += direction; 

    if (currentIndex >= totalSlides) {
        currentIndex = 0; 
    } else if (currentIndex < 0) {
        currentIndex = totalSlides - 1; 
    }

    track.style.transform = `translateX(-${currentIndex * 100}%)`;
    
    clearInterval(autoSlideInterval);
    startAutoSlide();
}

function startAutoSlide() {
    clearInterval(autoSlideInterval);
    
    autoSlideInterval = setInterval(() => {
        const track = document.getElementById('main-track');
        const totalSlides = document.querySelectorAll('#main-track .slide').length;
        
        if (!track || totalSlides === 0) return;

        currentIndex += 1;
        if (currentIndex >= totalSlides) {
            currentIndex = 0;
        }
        
        track.style.transform = `translateX(-${currentIndex * 100}%)`;
    }, 3000); 
}

/**
 * ==========================================
 * ส่วนที่ 5: ระบบข่าวประชาสัมพันธ์ (News Slider & Pop-up)
 * ==========================================
 */

// --- 5.1 ระบบเลื่อนการ์ดข่าว (News Navigation) ---
document.addEventListener("DOMContentLoaded", function() {
    const newsContainer = document.getElementById('newsContainer');
    const prevNewsBtn = document.querySelector('.prev-news');
    const nextNewsBtn = document.querySelector('.next-news');

    if (newsContainer && nextNewsBtn && prevNewsBtn) {
        nextNewsBtn.addEventListener('click', () => {
            const cardWidth = document.querySelector('.news-card').offsetWidth + 20; 
            newsContainer.scrollBy({ left: cardWidth, behavior: 'smooth' });
        });

        prevNewsBtn.addEventListener('click', () => {
            const cardWidth = document.querySelector('.news-card').offsetWidth + 20;
            newsContainer.scrollBy({ left: -cardWidth, behavior: 'smooth' });
        });
    }

    // --- 5.2 ระบบ Pop-up รายละเอียดข่าว (News Modal) ---
    const modal = document.getElementById("newsModal");
    const closeBtn = document.querySelector(".close-modal");
    
    const modalTitle = document.getElementById("modalTitle");
    const modalImage = document.getElementById("modalImage");
    const modalTextDetails = document.getElementById("modalTextDetails");
    const modalTextBody = document.getElementById("modalTextBody");      
    const modalTextAuthor = document.getElementById("modalTextAuthor");  

    const newsCards = document.querySelectorAll(".news-card");

    newsCards.forEach(card => {
        card.addEventListener("click", function() {
            const imgSrc = this.querySelector(".news-image img").src;
            const title = this.querySelector("h3").innerText;
            const desc = this.querySelector(".news-desc").innerHTML;
            
            const paragraphs = this.querySelectorAll("p");
            let detailsHtml = "";
            for (let i = 1; i < 3 && i < paragraphs.length; i++) { 
                detailsHtml += `<p>${paragraphs[i].innerHTML}</p>`;
            }

            modalTitle.innerText = title;
            modalImage.src = imgSrc;
            modalTextDetails.innerHTML = detailsHtml; 
            modalTextBody.innerHTML = desc;         

            modalTextAuthor.innerHTML = `<p><strong>แหล่งข้อมูล:</strong> มหาวิทยาลัยศรีนครินทรวิโรฒ</p><p><strong>ผู้เขียน/ภาพ:</strong> -</p>`;

            modal.style.display = "flex";
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener("click", function() {
            modal.style.display = "none";
        });
    }

    window.addEventListener("click", function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });
});

/**
 * ==========================================
 * ส่วนที่ 6: ระบบสไลด์บุคลากร (Staff Slider)
 * ==========================================
 */

const currentSlideIndex = {
    staff: 0
};

function moveSlide(sliderId, step) { 
    const track = document.getElementById(sliderId + '-track');
    if (!track) return;

    const slides = track.querySelectorAll('.slide');
    if (slides.length === 0) return;

    const slideWidth = slides[0].offsetWidth;
    const visibleSlides = Math.round(track.parentElement.offsetWidth / slideWidth);
    const maxIndex = slides.length - visibleSlides;

    currentSlideIndex[sliderId] += step;

    if (currentSlideIndex[sliderId] < 0) {
        currentSlideIndex[sliderId] = 0; 
    } else if (currentSlideIndex[sliderId] > maxIndex) {
        currentSlideIndex[sliderId] = maxIndex; 
    }

    track.style.transform = `translateX(-${currentSlideIndex[sliderId] * slideWidth}px)`;
}

/**
 * ==========================================
 * ส่วนที่ 7: ระบบปฏิทินกิจกรรม (Calendar)
 * ==========================================
 */

const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
let currentDate = new Date();

// ===== จุดที่ 3: events object อัปเดตจากปฏิทินการศึกษา 2568 =====
const events = {
    // ===== กรกฎาคม 2568 =====
    "2025-7-10":  { type: "mark-gray",  title: "วันอาสาฬหบูชา" },
    "2025-7-11":  { type: "mark-gray",  title: "วันเข้าพรรษา" },
    "2025-7-28":  { type: "mark-gray",  title: "วันเฉลิมพระชนมพรรษา ร.10" },

    // ===== สิงหาคม 2568 — เปิดภาคต้น =====
    "2025-8-4":   { type: "mark-green", title: "เปิดเรียน ภาคต้น 1/2568" },
    "2025-8-11":  { type: "mark-gray",  title: "วันหยุดพิเศษตามมติ ครม." },
    "2025-8-12":  { type: "mark-gray",  title: "วันเฉลิมพระชนมพรรษา ร.9 / วันแม่แห่งชาติ" },

    // ===== กันยายน–ตุลาคม 2568 — สอบกลางภาค 1 =====
    "2025-9-29":  { type: "mark-red",   title: "สอบกลางภาค 1/2568 (วันแรก)" },
    "2025-9-30":  { type: "mark-red",   title: "สอบกลางภาค 1/2568" },
    "2025-10-1":  { type: "mark-red",   title: "สอบกลางภาค 1/2568" },
    "2025-10-2":  { type: "mark-red",   title: "สอบกลางภาค 1/2568" },
    "2025-10-3":  { type: "mark-red",   title: "สอบกลางภาค 1/2568 (วันสุดท้าย)" },
    "2025-10-13": { type: "mark-gray",  title: "วันนวมินทรมหาราช" },
    "2025-10-23": { type: "mark-gray",  title: "วันปิยมหาราช" },

    // ===== พฤศจิกายน 2568 =====
    "2025-11-8":  { type: "mark-blue",  title: "SWU OPEN HOUSE 2025 (วันแรก)" },
    "2025-11-9":  { type: "mark-blue",  title: "SWU OPEN HOUSE 2025 (วันสุดท้าย)" },
    "2025-11-23": { type: "mark-gray",  title: "วันสุดท้ายของการเรียน ภาค 1/2568" },
    "2025-11-24": { type: "mark-red",   title: "สอบปลายภาค 1/2568 (วันแรก)" },
    "2025-11-25": { type: "mark-red",   title: "สอบปลายภาค 1/2568" },
    "2025-11-26": { type: "mark-red",   title: "สอบปลายภาค 1/2568" },
    "2025-11-27": { type: "mark-red",   title: "สอบปลายภาค 1/2568" },
    "2025-11-28": { type: "mark-red",   title: "สอบปลายภาค 1/2568" },

    // ===== ธันวาคม 2568 =====
    "2025-12-1":  { type: "mark-red",   title: "สอบปลายภาค 1/2568" },
    "2025-12-2":  { type: "mark-red",   title: "สอบปลายภาค 1/2568" },
    "2025-12-3":  { type: "mark-red",   title: "สอบปลายภาค 1/2568" },
    "2025-12-4":  { type: "mark-red",   title: "สอบปลายภาค 1/2568" },
    "2025-12-5":  { type: "mark-gray",  title: "วันพ่อแห่งชาติ" },
    "2025-12-8":  { type: "mark-red",   title: "สอบปลายภาค 1/2568 (วันสุดท้าย)" },
    "2025-12-9":  { type: "mark-green", title: "ปิดภาคต้น / วันสำเร็จการศึกษา" },
    "2025-12-10": { type: "mark-gray",  title: "วันรัฐธรรมนูญ" },
    "2025-12-31": { type: "mark-gray",  title: "วันสิ้นปี 2568" },

    // ===== มกราคม 2569 — เปิดภาคปลาย =====
    "2026-1-1":   { type: "mark-gray",  title: "วันขึ้นปีใหม่ 2569" },
    "2026-1-5":   { type: "mark-green", title: "เปิดเรียน ภาคปลาย 2/2568" },

    // ===== มีนาคม 2569 — สอบกลางภาค 2 =====
    "2026-3-3":   { type: "mark-gray",  title: "วันมาฆบูชา" },
    "2026-3-9":   { type: "mark-red",   title: "สอบกลางภาค 2/2568 (วันแรก)" },
    "2026-3-10":  { type: "mark-red",   title: "สอบกลางภาค 2/2568" },
    "2026-3-11":  { type: "mark-red",   title: "สอบกลางภาค 2/2568" },
    "2026-3-12":  { type: "mark-red",   title: "สอบกลางภาค 2/2568" },
    "2026-3-13":  { type: "mark-red",   title: "สอบกลางภาค 2/2568 (วันสุดท้าย)" },

    // ===== เมษายน 2569 =====
    "2026-4-6":   { type: "mark-gray",  title: "วันจักรี" },
    "2026-4-13":  { type: "mark-gray",  title: "วันสงกรานต์" },
    "2026-4-14":  { type: "mark-gray",  title: "วันสงกรานต์" },
    "2026-4-15":  { type: "mark-gray",  title: "วันสงกรานต์" },
    "2026-4-27":  { type: "mark-red",   title: "สอบปลายภาค นิสิต กศ.บ. ปี 3 (วันแรก)" },

    // ===== พฤษภาคม 2569 — สอบปลายภาค 2 =====
    "2026-5-3":   { type: "mark-gray",  title: "วันสุดท้ายของการเรียน ภาค 2/2568" },
    "2026-5-4":   { type: "mark-gray",  title: "วันฉัตรมงคล" },
    "2026-5-5":   { type: "mark-red",   title: "สอบปลายภาค 2/2568 (วันแรก)" },
    "2026-5-6":   { type: "mark-red",   title: "สอบปลายภาค 2/2568" },
    "2026-5-7":   { type: "mark-red",   title: "สอบปลายภาค 2/2568" },
    "2026-5-8":   { type: "mark-red",   title: "สอบปลายภาค 2/2568" },
    "2026-5-11":  { type: "mark-red",   title: "สอบปลายภาค 2/2568" },
    "2026-5-12":  { type: "mark-red",   title: "สอบปลายภาค 2/2568" },
    "2026-5-13":  { type: "mark-red",   title: "สอบปลายภาค 2/2568" },
    "2026-5-14":  { type: "mark-red",   title: "สอบปลายภาค 2/2568" },
    "2026-5-15":  { type: "mark-red",   title: "สอบปลายภาค 2/2568" },
    "2026-5-18":  { type: "mark-red",   title: "สอบปลายภาค 2/2568 (วันสุดท้าย)" },
    "2026-5-19":  { type: "mark-green", title: "ปิดภาคปลาย / วันสำเร็จการศึกษา" },
    "2026-5-25":  { type: "mark-green", title: "เปิดเรียน ภาคฤดูร้อน 3/2568" },

    // ===== มิถุนายน 2569 =====
    "2026-6-1":   { type: "mark-gray",  title: "ชดเชยวันวิสาขบูชา" },
    "2026-6-3":   { type: "mark-gray",  title: "วันเฉลิมพระชนมพรรษา สมเด็จพระนางเจ้าสุทิดาฯ" },
    "2026-6-22":  { type: "mark-red",   title: "สอบกลางภาค 3/2568 (วันแรก)" },
    "2026-6-23":  { type: "mark-red",   title: "สอบกลางภาค 3/2568" },
    "2026-6-24":  { type: "mark-red",   title: "สอบกลางภาค 3/2568" },
    "2026-6-25":  { type: "mark-red",   title: "สอบกลางภาค 3/2568" },
    "2026-6-26":  { type: "mark-red",   title: "สอบกลางภาค 3/2568 (วันสุดท้าย)" },

    // ===== กรกฎาคม 2569 — สอบปลายภาค 3 =====
    "2026-7-17":  { type: "mark-gray",  title: "วันสุดท้ายของการเรียน ภาค 3/2568" },
    "2026-7-20":  { type: "mark-red",   title: "สอบปลายภาค 3/2568 (วันแรก)" },
    "2026-7-21":  { type: "mark-red",   title: "สอบปลายภาค 3/2568" },
    "2026-7-22":  { type: "mark-red",   title: "สอบปลายภาค 3/2568" },
    "2026-7-23":  { type: "mark-red",   title: "สอบปลายภาค 3/2568" },
    "2026-7-24":  { type: "mark-red",   title: "สอบปลายภาค 3/2568 (วันสุดท้าย)" },
    "2026-7-27":  { type: "mark-green", title: "ปิดภาคฤดูร้อน" },
    "2026-7-28":  { type: "mark-gray",  title: "วันเฉลิมพระชนมพรรษา ร.10" },
    "2026-7-29":  { type: "mark-gray",  title: "วันอาสาฬหบูชา" },
    "2026-7-30":  { type: "mark-gray",  title: "วันเข้าพรรษา" },
};

function renderCalendar(date) {
    const year = date.getFullYear();
    const month = date.getMonth(); 

    // วันปัจจุบัน
    const today = new Date();
    const todayYear  = today.getFullYear();
    const todayMonth = today.getMonth();
    const todayDay   = today.getDate();
    
    const monthYearElem = document.getElementById("month-year");
    if (monthYearElem) monthYearElem.innerText = `${monthNames[month]} ${year}`;
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    const daysContainer = document.getElementById("calendar-days");
    if (!daysContainer) return;
    
    daysContainer.innerHTML = ""; 
    
    for (let i = 0; i < firstDay; i++) {
        daysContainer.innerHTML += `<div></div>`;
    }
    
    for (let i = 1; i <= daysInMonth; i++) {
        const dateString = `${year}-${month + 1}-${i}`;
        let eventClass = "";
        let titleAttr = "";
        let todayClass = "";
        
        if (events[dateString]) {
            eventClass = events[dateString].type;
            titleAttr = `title="${events[dateString].title}"`;
        }

        // ถ้าเป็นวันนี้ ให้เพิ่ม class mark-today
        if (year === todayYear && month === todayMonth && i === todayDay) {
            todayClass = "mark-today";
            // ถ้าวันนี้มี event อยู่ด้วย ให้ title บอกทั้งคู่
            if (!titleAttr) titleAttr = `title="วันนี้"`;
        }
        
        daysContainer.innerHTML += `<div class="${[eventClass, todayClass].filter(Boolean).join(' ')}" ${titleAttr}>${i}</div>`;
    }
}

function changeMonth(direction) {
    currentDate.setMonth(currentDate.getMonth() + direction);
    renderCalendar(currentDate);
}

// เรียกใช้งานปฏิทินทันที
renderCalendar(currentDate);

/**
 * ==========================================
 * ส่วนที่ 8: ระบบแจ้งเตือนคุ๊กกี้ (Cookie Banner)
 * ==========================================
 */

document.addEventListener("DOMContentLoaded", function() {
    const cookieBanner = document.getElementById("cookie-banner");
    const acceptBtn = document.getElementById("accept-cookie");
    const declineBtn = document.getElementById("decline-cookie");

    if (cookieBanner) {
        if (!localStorage.getItem("swu_cookie_consent")) {
            setTimeout(() => {
                cookieBanner.classList.add("show");
            }, 1000);
        }

        acceptBtn.addEventListener("click", function() {
            localStorage.setItem("swu_cookie_consent", "accepted"); 
            cookieBanner.classList.remove("show"); 
        });

        declineBtn.addEventListener("click", function() {
            localStorage.setItem("swu_cookie_consent", "declined"); 
            cookieBanner.classList.remove("show"); 
        });
    }
});

/**
 * ==========================================
 * การตั้งค่า Event Listeners สำหรับ Welcome Slider
 * ==========================================
 */

document.addEventListener("DOMContentLoaded", function() {
    const track = document.getElementById('main-track');
    const slides = document.querySelectorAll('#main-track .slide');
    
    if (track && slides.length > 0) {
        startAutoSlide();

        // Touch Events สำหรับมือถือ
        track.addEventListener('touchstart', function(event) {
            touchStartX = event.changedTouches[0].screenX;
        }, { passive: true });

        track.addEventListener('touchend', function(event) {
            touchEndX = event.changedTouches[0].screenX;
            handleSwipe(); 
        }, { passive: true });

        // Mouse Events สำหรับคอมพิวเตอร์
        track.addEventListener('mousedown', function(event) {
            touchStartX = event.screenX;
        });

        track.addEventListener('mouseup', function(event) {
            touchEndX = event.screenX;
            handleSwipe(); 
        });

        track.addEventListener('dragstart', function(event) {
            event.preventDefault();
        });
    }
});