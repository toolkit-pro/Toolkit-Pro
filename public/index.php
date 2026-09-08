<?php

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toolkit Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Hind Siliguri", system-ui, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            text-align: center;
            padding: 40px;
            max-width: 600px;
        }
        .logo {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            font-weight: 700;
        }
        p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        .status {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
        }
        .btn {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🌍</div>
        <h1>Toolkit Pro</h1>
        <p>বিশ্বের সবচেয়ে সম্পূর্ণ ও শক্তিশালী অনলাইন টুলস প্ল্যাটফর্ম</p>
        <div class="status">✅ সার্ভার সফলভাবে চলছে</div>
        <br>
        <a href="#" class="btn">🚀 শুরু করুন</a>
    </div>
</body>
</html>';
