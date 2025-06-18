// Visualização prévia da foto de perfil
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('profile-pic-input').addEventListener('change', function(event) {
        if (event.target.files && event.target.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                // Verificar se já existe uma imagem de perfil
                var profilePic = document.querySelector('.profile-pic');
                var profilePicPreview = document.querySelector('.profile-pic-preview');
                
                if (profilePicPreview) {
                    // Se já existe uma imagem, atualiza
                    profilePicPreview.src = e.target.result;
                } else if (profilePic) {
                    // Se só existe o placeholder, substitui
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'profile-pic-preview';
                    profilePic.parentNode.replaceChild(img, profilePic);
                }
            };
            
            reader.readAsDataURL(event.target.files[0]);
        }
    });
});
