<!DOCTYPE html>
<html>
<head>
    <title>Upload Multiple Word Documents</title>
</head>
<body>
    <h1>Upload Multiple Word Documents (DOCX)</h1>
    @if(session('error'))
        <p style="color: red;">{{ session('error') }}</p>
    @endif
    <form action="{{ route('merge') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="documents[]" multiple accept=".docx,.doc">
        <button type="submit">Upload & Merge</button>
    </form>
</body>
</html>