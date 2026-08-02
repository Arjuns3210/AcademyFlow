<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #212529;">
    <h2>New Live Demo Login</h2>
    <p>Someone just entered the live demo from the landing page.</p>

    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; border-color: #dee2e6;">
        <tr><td><strong>Name</strong></td><td>{{ $lead->name }}</td></tr>
        <tr><td><strong>Phone</strong></td><td>{{ $lead->phone }}</td></tr>
        <tr><td><strong>Institute</strong></td><td>{{ $lead->institute_name }}</td></tr>
        <tr><td><strong>Email</strong></td><td>{{ $lead->email }}</td></tr>
        <tr><td><strong>IP Address</strong></td><td>{{ $lead->ip_address }}</td></tr>
        <tr><td><strong>Time</strong></td><td>{{ $lead->created_at->format('d M Y, h:i A') }}</td></tr>
    </table>

    <p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>
