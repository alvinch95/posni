<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thai Baht Text</title>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f4f4f4;
    }

    .container {
        text-align: center;
    }

    h1 {
        margin-bottom: 20px;
    }

    input[type="text"] {
        padding: 10px;
        margin-bottom: 10px;
        width: 200px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
    }

    button {
        padding: 10px 20px;
        background-color: #007bff;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    button:hover {
        background-color: #0056b3;
    }

    #result {
        margin-top: 20px;
        font-size: 18px;
    }
</style>
</head>
<body>
<div class="container">
    <h1>Thai Baht Text</h1>
    <input type="text" id="numberInput" placeholder="Enter number">
    <button id="translateBtn">Translate</button>
    <div id="result"></div>
</div>

<script src="/js/thai-baht-text.js"></script>
<script>

    document.getElementById("translateBtn").addEventListener("click", function() {
        var number = parseFloat(document.getElementById("numberInput").value);
        var thaiText = THBText(number);

        document.getElementById("result").innerText = thaiText;
    });
</script>
</body>
</html>
