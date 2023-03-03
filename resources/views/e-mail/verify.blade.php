<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;0,800;1,300;1,400;1,600;1,700;1,800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Open Sans', sans-serif;
        }

        .container {
            max-width: 500px;
            margin: auto;
            padding: 20px;
        }

        img {
            width: 60px;
            height: 60px;
            margin-right: 20px;
            border-radius: 100%;
            margin-bottom: 20px;
            box-shadow: 0px 2px 5px 2px #999999;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        .header {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            flex-direction: column;
        }

        .logo {
            font-size: 30px;
            font-weight: 900;
            display: flex;
            align-items: center;
        }

        strong {
            font-size: 30px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="{{asset('logo.png') }}">Numidia
            </div>
            <h2>Welcome to Numidia The Place to Learn</h2>
        </div>
        <p style="text-transform:capitalize;">Dear {{ $data['name']}},</p>

        <p>Thank you for registering with our platform.</p>
        <p>Please use the following code to verify your email address:</p>
        <p><strong>{{ $data['code'] }}</strong></p>
        <p>If you did not request this verification, please ignore this email.</p>
        <p>Best regards,</p>
        <p>The Team </p>
    </div>
</body>

</html>