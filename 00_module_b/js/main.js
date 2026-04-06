document.addEventListener('DOMContentLoaded', () => {
    // 3. 互動餐盤邏輯
    const foodData = {
        fruit: { title: '水果類', slogan: '每餐水果拳頭大', portion: '1份(大約1個拳頭大小)', icon: '🍎' },
        veg: { title: '蔬菜類', slogan: '菜比水果多一點', portion: '燙熟約半碗', icon: '🥬' },
        grain: { title: '全穀雜糧類', slogan: '飯跟蔬菜一樣多', portion: '1碗', icon: '🍚' },
        protein: { title: '豆魚蛋肉', slogan: '豆魚蛋肉一掌心', portion: '1掌心大', icon: '🥩' },
        dairy: { title: '乳品類', slogan: '每天早晚一杯奶', portion: '1杯(240ml)', icon: '🥛' },
        fat: { title: '油脂與堅果', slogan: '堅果種子一茶匙', portion: '1茶匙', icon: '🥜' }
    };

    const icons = document.querySelectorAll('.food-icon');
    const overlay = document.getElementById('plate-overlay');
    const overlayClose = document.querySelector('.close-overlay');
    
    function showOverlay(data) {
        overlay.querySelector('.o-icon').textContent = data.icon;
        overlay.querySelector('.o-title').textContent = data.title;
        overlay.querySelector('.o-slogan').textContent = data.slogan;
        overlay.querySelector('.o-portion').textContent = data.portion;
        overlay.style.display = 'flex';
        overlay.setAttribute('aria-hidden', 'false');
    }

    function hideOverlay() {
        overlay.style.display = 'none';
        overlay.setAttribute('aria-hidden', 'true');
        icons.forEach(i => i.classList.remove('active'));
    }

    icons.forEach(btn => {
        btn.addEventListener('mouseenter', () => btn.classList.add('active'));
        btn.addEventListener('mouseleave', () => { if(overlay.style.display === 'none') btn.classList.remove('active'); });
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            icons.forEach(i => i.classList.remove('active'));
            btn.classList.add('active');
            showOverlay(foodData[btn.dataset.target]);
        });
    });

    overlayClose.addEventListener('click', hideOverlay);
    document.addEventListener('click', (e) => {
        if(!e.target.closest('#plate-container') && !e.target.closest('.icons-left')) {
            hideOverlay();
        }
    });

    // 4. 影片滾動播放 (IntersectionObserver)
    const video = document.getElementById('autoVideo');
    if (video) {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    video.play().catch(e=>console.log(e));
                } else {
                    video.pause();
                }
            });
        }, { threshold: 0.5 }); // 進入視野 50% 自動播放
        obs.observe(video);
    }

    // 5. 手冊下載 ZIP
    document.getElementById('downloadAllBtn')?.addEventListener('click', () => {
        if(typeof JSZip === 'undefined') { alert('JSZip 載入失敗！'); return; }
        const zip = new JSZip();
        zip.file("國民飲食指標手冊.txt", "這是假的手冊內容 PDF 替代");
        zip.file("我的餐盤手冊.txt", "這是假的手冊內容 PDF 替代");
        zip.file("每日飲食指南手冊.txt", "這是假的手冊內容 PDF 替代");
        
        zip.generateAsync({type:"blob"}).then(function(content) {
            const url = URL.createObjectURL(content);
            const a = document.createElement("a");
            a.href = url;
            a.download = "all_manuals.zip";
            a.click();
            URL.revokeObjectURL(url);
        });
    });

    // 6. 三餐推薦 (巢狀 Tabs + Keyboard nav)
    const handleTabs = (tabSelector, panelClass) => {
        const tabs = document.querySelectorAll(tabSelector);
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.setAttribute('aria-selected', 'false'));
                tabs.forEach(t => t.classList.remove('active'));
                document.querySelectorAll(panelClass).forEach(p => {p.style.display = 'none'; p.classList.remove('active')});
                
                tab.setAttribute('aria-selected', 'true');
                tab.classList.add('active');
                const target = document.getElementById(tab.dataset.tab || tab.dataset.sub);
                target.style.display = 'block';
                target.classList.add('active');
            });
            // 鍵盤左右支援
            tab.addEventListener('keydown', (e) => {
                let targetIndex = index;
                if (e.key === 'ArrowRight') targetIndex = (index + 1) % tabs.length;
                else if (e.key === 'ArrowLeft') targetIndex = (index - 1 + tabs.length) % tabs.length;
                else return;
                
                tabs[targetIndex].focus();
                tabs[targetIndex].click();
            });
        });
    };
    handleTabs('.tab-list .tab-btn', '.tab-panel');
    handleTabs('.sub-tab-list .sub-tab-btn', '.sub-panel');

    // 8. 表單驗證 (自訂阻擋器或直接靠原生 pattern)
    document.getElementById('consultForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const phone = this.phone.value;
        if(!/^[0-9]{4}-[0-9]{6}$/.test(phone)) {
            alert('電話格式錯誤！應為 09xx-xxxxxx');
            return;
        }
        alert('表單已成功送出！(此為前端模擬)');
        this.reset();
    });
});
