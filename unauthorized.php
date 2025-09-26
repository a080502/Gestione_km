<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesso Negato</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Nunito', sans-serif; /* Font più moderna */
        }

        .unauthorized-container {
            background-color: #fff;
            padding: 50px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 1px solid #eee;
        }

        .alert-icon {
            font-size: 4rem;
            color: #ffc107; /* Giallo ambra Bootstrap per l'avviso */
            margin-bottom: 30px;
            animation: pulse 1.5s infinite alternate; /* Aggiunta animazione */
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }

        h1 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 2.5rem;
        }

        p {
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        #countdown-container {
            font-size: 1.8rem;
            color: #007bff; /* Blu Bootstrap */
            font-weight: bold;
        }

        #countdown-text {
            margin-right: 5px;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="unauthorized-container">
    <i class="bi bi-shield-exclamation alert-icon"></i>
    <h1>Accesso Negato</h1>
    <p>Sembra che tu non abbia i permessi necessari per visualizzare questa pagina.</p>
    <p>Sarai reindirizzato alla pagina principale in <span id="countdown-container"><span id="countdown-text"></span><span id="countdown">4</span></span> secondi...</p>
</div>

<script>
    let countdownElement = document.getElementById('countdown');
    let countdownTextElement = document.getElementById('countdown-text');
    let countdownTime = 3;
    countdownTextElement.textContent = " "; // Puoi aggiungere un testo prima del numero se vuoi

    function updateCountdown() {
        countdownElement.textContent = countdownTime;
        countdownTime--;

        if (countdownTime < 0) {
            window.location.href = 'index.php';
        } else {
            setTimeout(updateCountdown, 1000);
        }
    }

    updateCountdown();
</script>
</body>
</html>