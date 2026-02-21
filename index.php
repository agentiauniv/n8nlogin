<?php
session_start();

/* ============================== CONFIGURATION SUPABASE ================================= */

$project_url = "https://uhqqzlpaybcyxrepisgi.supabase.co";
$api_key = "TON_API_KEY_ICI";

/* ============================== LOGIN SUPABASE ================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {

    $email = trim($_POST["email"]);
    $password_input = trim($_POST["password"]);

    $url = $project_url . "/rest/v1/login?select=*";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $api_key",
        "Authorization: Bearer $api_key",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    $login_success = false;

    if (is_array($data)) {
        foreach ($data as $user) {
            if (
                trim($user["email"]) === $email &&
                trim($user["password"]) === $password_input
            ) {
                $_SESSION["student_id"] = $user["Matricule"];
                $_SESSION["email"] = $user["email"];
                $login_success = true;
                break;
            }
        }
    }

    if (!$login_success) {
        $error_message = "❌ Email ou mot de passe incorrect.";
    }
}

/* ============================== LOGOUT ================================= */

if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Agent IA Université</title>
</head>

<body>

<?php if (!isset($_SESSION["student_id"])): ?>

    <!-- ================= LOGIN ================= -->

    <h2>Connexion Étudiant</h2>

    <?php if (isset($error_message)) echo "<p>$error_message</p>"; ?>

    <form method="POST">
        <input type="hidden" name="login" value="1">

        <label>Email :</label><br>
        <input type="email" name="email" required><br><br>

        <label>Mot de passe :</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Se connecter</button>
    </form>

<?php else: ?>

    <!-- ================= CHAT ================= -->

    <h2>Bienvenue <?php echo htmlspecialchars($_SESSION["email"]); ?></h2>
    <a href="?logout=1">Déconnexion</a>

    <hr>

    <h3>Agent IA</h3>

    <input type="text" id="question" placeholder="Posez votre question..." style="width:300px;">
    <button onclick="envoyerMessage()">Envoyer</button>

    <div id="response" style="margin-top:20px;"></div>

    <script>
    async function envoyerMessage() {

        const message = document.getElementById("question").value;

        if (!message) {
            alert("Veuillez écrire une question");
            return;
        }

        try {
            const response = await fetch("https://n8nlogin-11.onrender.com/webhook/agent", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    message: message,
                    student_id: "<?php echo $_SESSION['student_id']; ?>"
                })
            });

            const data = await response.json();

            console.log("Réponse reçue :", data);

            const container = document.getElementById("response");
            container.innerHTML = "";

            if (data.imageUrl) {
                const img = document.createElement("img");
                img.src = data.imageUrl;
                img.style.width = "600px";
                img.style.marginTop = "20px";
                container.appendChild(img);
            } 
            else if (data.message) {
                container.innerHTML = data.message;
            } 
            else {
                container.innerHTML = "❌ Aucune réponse reçue.";
            }

        } catch (error) {
            console.error("Erreur :", error);
            document.getElementById("response").innerHTML = "❌ Erreur serveur.";
        }
    }
    </script>

<?php endif; ?>

</body>
</html>
