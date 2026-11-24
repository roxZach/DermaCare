<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>File too large · DermaCare AI</title>
<link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5" style="max-width:640px">
<div class="card shadow-sm"><div class="card-body p-4">
    <h4>The file is too large</h4>
    <p>The upload is bigger than the limit set in PHP's <code>php.ini</code>.</p>
    <ul>
        <li>Increase <code>upload_max_filesize</code> and <code>post_max_size</code> (for example to <code>200M</code>) in php.ini and restart the server, or</li>
        <li>for AI model files: copy the <code>.pt</code> file into <code>ai-service/models</code> and register it by name on the AI model page.</li>
    </ul>
    <a href="javascript:history.back()" class="btn btn-primary">Go back</a>
</div></div></div></body></html>
