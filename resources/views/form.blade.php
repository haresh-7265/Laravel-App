<h1>form with CSRF token</h1>

<form method="POST" action="/submit">
    @csrf
    <input type="text" name="name" placeholder="Enter name" />
    <button type="submit">Submit</button>
</form>