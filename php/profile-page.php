<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HiJinx - Proyecto BDM y WEB II por Victor y Rebeca</title>
    <link href="https://fonts.googleapis.com/css2?family=Quantico&display=swap" rel="stylesheet">
    <!-- IconScout CDN -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v2.1.6/css/unicons.css">
    <!-- Stylesheet -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/profile.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>
    <!-- Profile Page -->
    <div class="profile-page">
        <div class="container">
            <div class="profile-header">
                <div class="profile-photo">
                    <img src="../assets/profile_pics/profile-1.png" alt="">
                </div>
                <div class="profile-info">
                    <h2>Usuario</h2>
                    <p>Vive en Cancún</p>
                    <p>Estudió en Universidad de Autónoma de Nuevo León</p>
                </div>
            </div>
            <div class="profile-content">
                <div class="profile-posts">
                    <h3>Publicaciones</h3>
                    <div class="post">
                        <div class="post-header">
                            <div class="profile-photo">
                                <img src="../assets/profile_pics/profile-1.png" alt="">
                            </div>
                            <div class="profile-info">
                                <h4>Victor</h4>
                                <p>Estudiante de Ingeniería en Sistemas</p>
                                <p>Universidad de El Salvador</p>
                            </div>
                        </div>
                        <div class="post-content">
                            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, voluptates.</p>
                        </div>
                    </div>
                </div>
                <div class="profile-friends">
                    <h3>Amigos</h3>
                    <div class="friend">
                        <div class="profile-photo">
                            <img src="../assets/profile_pics/profile-1.png" alt="">
                        </div>
                        <div class="profile-info">
                            <h4>Victor</h4>
                            <p>Estudiante de Ingeniería en Sistemas</p>
                            <p>Universidad de El Salvador</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/profile.js"></script>
</body>
</html>