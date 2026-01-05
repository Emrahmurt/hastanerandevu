document.addEventListener('DOMContentLoaded', function() {

    // 1. MESAJLARI OTOMATİK GİZLE
    const mesajlar = document.querySelectorAll('.mesaj');
    if (mesajlar.length > 0) {
        setTimeout(function() {
            mesajlar.forEach(msg => {
                msg.style.transition = "opacity 0.5s ease";
                msg.style.opacity = "0";
                setTimeout(() => msg.remove(), 500);
            });
        }, 4000);
    }

    // 2. KAYIT FORMU KONTROLÜ (TC ve Şifre)
    const kayitFormu = document.querySelector('form[action="kayit.php"]');
    if (kayitFormu) {
        kayitFormu.addEventListener('submit', function(e) {
            const tcInput = kayitFormu.querySelector('input[name="tc_no"]');
            const passInput = kayitFormu.querySelector('input[name="parola"]');
            let hatalar = [];

            if (tcInput) {
                const tcVal = tcInput.value;
                if (isNaN(tcVal) || tcVal.length !== 11) {
                    hatalar.push("Kayıt için TC Kimlik Numarası 11 haneli bir sayı olmalıdır!");
                }
            }
            if (passInput && passInput.value.length < 6) {
                hatalar.push("Güvenliğiniz için parolanız en az 6 karakter olmalıdır.");
            }
            if (hatalar.length > 0) {
                e.preventDefault();
                alert(hatalar.join("\n"));
            }
        });
    }

    // 3. GİRİŞ FORMU KONTROLÜ (YENİ EKLENDİ)
    const loginFormu = document.querySelector('form[action="login.php"]');
    if (loginFormu) {
        loginFormu.addEventListener('submit', function(e) {
            const girisInput = loginFormu.querySelector('input[name="kullanici_girisi"]');
            const passInput = loginFormu.querySelector('input[name="parola"]');
            let hatalar = [];

            // Boş alan kontrolü
            if (girisInput.value.trim() === "") {
                hatalar.push("Lütfen E-posta veya TC Kimlik Numaranızı girin.");
            } 
            // Eğer girilen değer SADECE RAKAM ise (TC ile girmeye çalışıyorsa)
            else if (/^\d+$/.test(girisInput.value)) {
                if (girisInput.value.length !== 11) {
                    hatalar.push("TC Kimlik Numarası ile giriş yapıyorsanız 11 haneli olmalıdır!");
                }
            }

            if (passInput.value.trim() === "") {
                hatalar.push("Lütfen parolanızı girin.");
            }

            if (hatalar.length > 0) {
                e.preventDefault();
                alert(hatalar.join("\n"));
            }
        });
    }
    
    // 4. SİLME ONAYI
    const silButonlari = document.querySelectorAll('.btn-danger, button[style*="dc3545"]');
    silButonlari.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm("Bu işlemi yapmak istediğinize emin misiniz?")) {
                e.preventDefault();
            }
        });
    });

});