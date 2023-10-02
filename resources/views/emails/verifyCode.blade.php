<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify your email address</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            padding: 20px;
        }

        .container {
            background-color: #000000;
            border-radius: 5px;
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            max-width: 200px;
        }

        h1 {
            color: #3498db;
            font-size: 32px;
            margin-top: 0;
        }

        p {
            font-size: 20px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        h2 {
            color: #3498db;
            font-size: 40px;
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .cta {
            display: block;
            background-color: #3498db;
            color: #ffffff;
            text-align: center;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 24px;
            margin-top: 20px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 16px;
        }

        .social {
            margin-top: 20px;
            text-align: center;
        }

        .social a {
            display: inline-block;
            margin-right: 10px;
            color: #3498db;
            font-size: 24px;
            transition: color 0.2s ease-in-out;
        }

        .social a:hover {
            color: #2980b9;
        }

    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: #95b17c; border-bottom: 0.001em solid #ffffff;">happiness.</h1>


        <p style="color: #ffffff; padding-top: 50px; padding-bottom: 50px; font-size:25px">Verification code to login to happiness:</p>


        <h2 class="cta">{{ $code }}</h2>
        <p style="color: #ffffff; font-size: 12px ; padding-top: 50px; padding-bottom: 30px;">This code valid for 10 mins.Keep your Verification code confidential</p>
    </div>
</body>
</html>
