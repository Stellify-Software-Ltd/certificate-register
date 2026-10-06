<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Certificate register')</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#fafafa;color:#18181b;line-height:1.5}
.wrap{max-width:860px;margin:0 auto;padding:20px 16px 60px}
h1{font-size:22px;font-weight:700;margin:18px 0 4px}
h2{font-size:16px;font-weight:700;margin:0 0 8px}
.sub{color:#71717a;font-size:14px;margin-bottom:18px}
.card{background:#fff;border:1px solid #e4e4e7;border-radius:12px;padding:16px;margin-bottom:14px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.lbl{display:block;font-size:13px;font-weight:600;margin:10px 0 4px}
.hint{color:#71717a;font-size:12px;margin-top:2px}
.inp,.sel,textarea.inp{width:100%;border:1px solid #d4d4d8;border-radius:8px;padding:10px 12px;font-size:15px;background:#fff;font-family:inherit}
.inp:focus,.sel:focus{outline:2px solid #18181b;outline-offset:1px}
.btn{display:inline-block;background:#18181b;color:#fff;border:0;border-radius:8px;padding:11px 16px;font-size:15px;font-weight:600;cursor:pointer;text-decoration:none}
.btn.ghost{background:#fff;color:#18181b;border:1px solid #d4d4d8}
.btn.danger{background:#dc2626}
.btn.sm{padding:7px 12px;font-size:13px}
.muted{color:#71717a;font-size:13px}
.err{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;padding:10px 12px;font-size:14px;margin:10px 0}
.notice{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#166534;padding:10px 12px;font-size:14px;margin:10px 0}
.nav{display:flex;gap:14px;align-items:center;font-size:14px;padding:12px 0;border-bottom:1px solid #e4e4e7;flex-wrap:wrap}
.nav a{color:#18181b;text-decoration:none;font-weight:600}
.nav a:hover{text-decoration:underline}
.nav form{margin-left:auto}
.nav .out{border:0;background:none;color:#71717a;font-size:13px;cursor:pointer}
table{width:100%;border-collapse:collapse;font-size:14px}
th{text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.03em;color:#71717a;padding:8px 8px;border-bottom:1px solid #e4e4e7}
th a{color:#71717a;text-decoration:none}
th a:hover{text-decoration:underline}
td{padding:9px 8px;border-bottom:1px solid #f4f4f5;vertical-align:top}
td.num,th.num{text-align:right;font-variant-numeric:tabular-nums}
td a{color:#18181b}
.code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;white-space:nowrap}
.tag{font-size:11px;border-radius:999px;padding:2px 8px;white-space:nowrap;border:1px solid}
.tag.valid{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
.tag.soon{background:#fef3c7;border-color:#fde68a;color:#92400e}
.tag.expired{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.tag.none{background:#f4f4f5;border-color:#e4e4e7;color:#52525b}
.tag.superseded{background:#f4f4f5;border-color:#e4e4e7;color:#71717a}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:0 10px}
@media (max-width:560px){.grid2{grid-template-columns:1fr}}
.big{font-size:26px;font-weight:700}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap}
.filterbar{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end}
.filterbar>div{flex:1 1 140px;min-width:120px}
.bucket-count{font-size:13px;font-weight:400;color:#71717a}
.overdue{color:#b91c1c;font-weight:600}
.tablewrap{overflow-x:auto}
</style>
</head>
<body>
<div class="wrap">
    @if (session('operator_authenticated'))
        <nav class="nav">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('records.index') }}">Register</a>
            <a href="{{ route('records.create') }}">Add record</a>
            <a href="{{ route('types.index') }}">Types</a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="out" type="submit">Sign out</button>
            </form>
        </nav>
    @endif

    @if (session('status'))
        <div class="notice">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    @yield('content')
</div>
</body>
</html>
