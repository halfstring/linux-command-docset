<?php
require_once __DIR__ . '/vendor/autoload.php';


class GenLinuxCommandDocset
{
    private $sourceDir, $htmlDir;
    private             $allMD = [];
    private $tags = [];

    public function __construct($sourceDir, $htmlDir)
    {
        $this->sourceDir = $sourceDir;
        $this->htmlDir   = $htmlDir;
    }

    private function _readMD()
    {
        $handle = opendir($this->sourceDir);
        while ($file = readdir($handle)) {
            if ($file !== '..' && $file !== '.') {
                $f = $this->sourceDir . $file;

                if (is_file($f)) {
                    $this->allMD[] = $file;
                }
            }
        }

        return $this;
    }

    private function _genHtmlByGithub($md)
    {
        $file    = $this->sourceDir . $md;
        $content = file_get_contents($file);

        $this->tags[str_replace('.md','',$md)] = $md . '.html';
        $htmlFile = $this->htmlDir . $md . '.html';

        $url  = "https://api.github.com/markdown";
        $data = json_encode(["text" => $content], JSON_UNESCAPED_UNICODE);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Accept: application/vnd.github.v3+json",
            'Authorization: token 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',
            'User-Agent: GitHub-username'
        ));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($curl);
        curl_close($curl);

        $htmlContent = <<<HTMLCONTENT
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Document</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="description" content="Description">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
  <link rel="stylesheet" href="css/markdown-github.css">
  
</head>
<body>
{$response}
</body>
</html>
HTMLCONTENT;

        file_put_contents($htmlFile, $htmlContent);

        echo "\t" . $htmlFile, PHP_EOL;
        return true;
    }

    private function _genHtml($md)
    {
        $file    = $this->sourceDir . $md;
        $content = file_get_contents($file);

        $htmlFile = $this->htmlDir . $md . '.html';

        $logic = new Parsedown();
        $logic->setMarkupEscaped(false);
        $logic->setSafeMode(false);
        $htmlBody    = $logic->text($content);
        $htmlContent = <<<HTMLCONTENT
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Document</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="description" content="Description">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
  <link rel="stylesheet" href="css/markdown-github.css">
  <style>
	.markdown-body {
		box-sizing: border-box;
		min-width: 200px;
		max-width: 980px;
		margin: 0 auto;
		padding: 45px;
	}

	@media (max-width: 767px) {
		.markdown-body {
			padding: 15px;
		}
	}
    body {
        box-sizing: border-box;
        min-width: 200px;
        max-width: 980px;
        margin: 0 auto;
        padding: 45px;
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-fork-ribbon-css/0.2.3/gh-fork-ribbon.min.css">
    <style>
        .github-fork-ribbon:before {
            background-color: #121612;
        }
    </style>
</head>
<body>
{$htmlBody}
</body>
</html>
HTMLCONTENT;

        file_put_contents($htmlFile, $htmlContent);

        return true;
    }


    public function genSql()
    {
        $sqlTpl = "INSERT INTO `searchIndex`(name, type, path) VALUES ('{KEYWORD}','Keyword','{HTML-TPL}')";

        $sqls = [];

        foreach ($this->tags as $tag => $html){
            $sqls[]   = strtr($sqlTpl, [
                "{KEYWORD}"  => $tag,
                "{HTML-TPL}" => $html
            ]);

        }

        file_put_contents("docset.sql", implode(";\n", $sqls));

        return true;
    }


    public function handler()
    {
        $this->_readMD();

        if (empty($this->allMD)) {
            die("Markdown files is empty.");
        }

        foreach ($this->allMD as $_md) {
            echo "markdown->html: success, ", $_md, PHP_EOL;
            $this->_genHtmlByGithub($_md);
        }

        $this->genSql();

        return true;
    }


}


$sourceDir = __DIR__ . '/source/linux-command-master/command/';
$htmlDir   = __DIR__ . '/html/';

$logic = new GenLinuxCommandDocset($sourceDir, $htmlDir);
$logic->handler();

echo "over", PHP_EOL;



