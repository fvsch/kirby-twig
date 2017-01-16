<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title><?php echo $title ?></title>
    <style>
    html {
        font-family: sans-serif;
        background-color: #eee;
    }
    body {
        background-color: #fff;
        color: #333;
        margin: 0;
        line-height: 1.5;
    }
    div {
        padding: 1rem 1.5rem;
    }
    h1 {
        margin: .5rem 0 1rem;
        font-size: 140%;
        line-height: 1.2;
        font-family: monospace, monospace;
    }
    h2 {
        margin: -.75rem 0 1rem;
        font-size: 90%;
        line-height: 1.3;
        font-weight: normal;
        font-family: monospace, monospace;
        color: #707070;
    }
    p {
        margin: .5rem 0;
    }
    p > span {
        padding: .15em .3em;
        box-decoration-break: clone;
        color: #fff;
        background-color: darkred;
    }
    pre, code {
        font-family: monospace, monospace;
    }
    pre {
        margin: 0;
        padding: 1.5rem;
        white-space: pre-wrap;
        font-size: 90%;
        -webkit-tab-size: 4;
        -moz-tab-size: 4;
        tab-size: 4;
        color: #999;
        background: black;
    }
    pre code {
        display: block;
    }
    pre mark {
        color: white;
        background: none;
    }

    @media (min-width: 40em) {
        html {
            padding: 1em;
        }
        body {
            width: 60em;
            max-width: 100%;
            margin: 1em auto;
            border: solid 1px #ccc;
        }
    }

    @media (max-width: 29.99em) {
        div {
            padding: 1.5rem 1rem;
        }
        pre {
            padding: 1.5rem 1rem;
            overflow-x: auto;
        }
        pre code {
            padding-left: 0;
        }
    }

    @media (min-width: 30em) {
        [data-line] {
            display: inline-block;
            box-sizing: border-box;
            width: 100%;
            padding-left: 6ch;
        }
        [data-line]::before {
            content: attr(data-line);
            display: inline-block;
            box-sizing: border-box;
            width: 5ch;
            margin-left: -7ch;
            padding-right: 2ch;
            text-align: right;
            color: #666;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        mark[data-line]::before {
            color: #bbb;
        }
    }
    </style>
</head>
<body>
    <div>
        <h1><?php echo $title ?></h1>
        <?php if (!empty($subtitle)) { echo "<h2>$subtitle</h2>\n"; } ?>
        <?php if (!empty($message)) { echo "<p><span>$message</span></p>\n"; } ?>
    </div>
    <?php if (!empty($code)) { echo "<pre><code>$code</code></pre>"; } ?>
</body>
</html>
