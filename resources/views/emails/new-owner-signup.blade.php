<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; max-width: 500px; margin: 40px auto; color: #333; }
        .label { font-size: 12px; color: #999; text-transform: uppercase; letter-spacing: 0.1em; }
        .value { font-size: 16px; color: #0a0a0a; margin-bottom: 20px; }
        .btn { display: inline-block; background: #0a0a0a; color: #fff; padding: 12px 28px; text-decoration: none; font-size: 14px; margin-top: 16px; }
    </style>
</head>
<body>
    <h2 style="font-weight:300;letter-spacing:0.1em;">New Owner Signup</h2>
    <hr style="border:none;border-top:1px solid #eee;margin:20px 0;">

    <p class="label">Name</p>
    <p class="value">{{ $user->name }}</p>

    <p class="label">Email</p>
    <p class="value">{{ $user->email }}</p>

    <p class="label">Store Name</p>
    <p class="value">{{ $user->store?->name }}</p>

    <p class="label">Signed up</p>
    <p class="value">{{ $user->created_at->format('M d, Y — h:i A') }}</p>

    <p style="color:#666;font-size:13px;">Log in to your super admin dashboard to activate this account.</p>
</body>
</html>