#!/usr/bin/env php
<?php

class DockerApps
{
    static $dcDir = '/web/controller/run/', $sep = "---" . PHP_EOL, $detailedFile = __DIR__ . '/.docker_show_details', $services = ['proxy', 'mysql', 'redis'];

    public function __construct()
    {
        $this->readApps();
    }
    
    public function reverseApp($app) {
    	return join('.', array_reverse(explode('.', $app)));
    }

    public function readApps()
    {
        $files = [];
        $d = dir(static::$dcDir);

        while (false !== ($app = $d->read())) {
            if ($app !== "." && $app !== ".." && $app !== ".gitignore") {
                $files[] = $this->reverseApp($app);
            }
        }

        $serving = $this->serving();

        $d->close();

        $running = 0;
        $stopped = 0;
        $coutput = '';
        $soutput = '';
        // in_array($entry, ['mysql, redis']);

        sort($files);
        // usort($files, fn($app1, $app2) => ($app1 > $app2 || $this->isService($app1)) ? 1 : -1);

        foreach ($files as $appr) {
        		$app = $this->reverseApp($appr);
            if ($this->detailed() && count($containers = $this->status($app))) {
                $stop = $this->down($app);
                $restart = $this->restart($app);
                $tail = $this->tail($app);
                $url = $this->url($app);
                $sym = '';
                $coutput .= $sym . $appr . ': ' . count($containers) . ' containers' . PHP_EOL;
                $coutput .= $sym . '--Restart ' . $app . ' | bash="' . $restart . '" terminal=true refresh=true iconName=media-playlist-repeat-song-symbolic' . PHP_EOL;
                $coutput .= $sym . '--Stop ' . $app . ' | bash="' . $stop . '" terminal=true refresh=true iconName=dialog-error-symbolic' . PHP_EOL;
                $coutput .= $sym . '--Tail logs | bash="' . $tail . '" terminal=true refresh=true iconName=view-more-symbolic' . PHP_EOL;
                if ($url) {
                	$coutput .= $sym . '--Browse | bash="xdg-open ' . $url . '" terminal=false refresh=true iconName=chrome' . PHP_EOL;
              	}
                $running++;
                $coutput .= $sym . '-- ---' . PHP_EOL;
                foreach ($containers as $container) {
                    $run = $this->exec(explode("|", $container)[0]);
                    $coutput .= $sym . '--' . explode("|", $container)[0] . ' | bash="' . $run . '" terminal=false refresh=false iconName=utilities-terminal-symbolic' . PHP_EOL;
                }
            } else {
                $stopped++;
                $com = $this->up($app);
                $cont = '';
                $soutput .= '--' . $appr . $cont . ' | bash="' . $com . '" terminal=true refresh=true iconName=media-playback-start-symbolic' . PHP_EOL;
            }
        }

        $output = ($serving ? '🌏' : '🌑') . ' ' . ' ' . ($this->detailed() ? $running : '?') . PHP_EOL;
        $output .= static::$sep;
        if ($this->detailed()) {
            $output .= 'Running apps:' . PHP_EOL . $coutput;
            $output .= static::$sep;
        }
        $output .= ($this->detailed() ? 'Stopped apps:' : 'Apps:') . PHP_EOL;
        $output .= $soutput;
        $output .= static::$sep;
        $output .= 'Service' . PHP_EOL;
        $output .= '--Stop docker | bash="sudo systemctl stop docker.socket && exit" terminal=true refresh=true iconName=dialog-error-symbolic' . PHP_EOL;
        $output .= '--Start docker | bash="sudo systemctl start docker.service && exit" terminal=true refresh=true iconName=media-playback-start-symbolic' . PHP_EOL;
        $output .= '--Restart docker | bash="sudo service docker restart 2>1 && exit" terminal=true refresh=true iconName=media-playlist-repeat-song-symbolic' . PHP_EOL;
        $output .= static::$sep;
        /*$output .= (! $this->detailed() ?
                'Load app details | bash="touch '.static::$detailedFile.' && exit" terminal=true refresh=true iconName=user-available-symbolic' :
                'Hide app details | bash="rm '.static::$detailedFile.' && exit" terminal=true refresh=true iconName=user-offline-symbolic').PHP_EOL;*/
        $output .= ($serving ?
                'Public: ' . $serving . ' | bash="sudo ufw-docker delete allow proxy && exit" terminal=true refresh=true iconName=network-receive-symbolic' :
                'Not public | bash="sudo ufw-docker allow proxy && exit" terminal=true refresh=true iconName=network-error-symbolic') . PHP_EOL;
        $output .= 'Stop all applications | bash="for file in /web/controller/run/*; do $file stop; done && exit" terminal=true refresh=true iconName=window-close-symbolic' . PHP_EOL;
        $output .= 'Renew certifficates | bash="docker exec -it letsencrypt /app/force_renew && exit" terminal=true refresh=true iconName=mail-send-receive-symbolic' . PHP_EOL;

        echo $output;
    }

    public function isService($app)
    {
        return in_array($app, static::$services);
    }

    public function detailed()
    {
        return true || file_exists(static::$detailedFile);
    }

    public function status($app)
    {
        $containers = [];
        exec($this->ps($app) . ' 2>/dev/null', $containers);

        return $containers;
    }

    public function ps($app)
    {
        $composerName = str_replace('.', '', $app);

        return 'docker ps --format="{{.Names}}|{{.Image}}|{{.RunningFor}}|{{.Status}}" -f label=com.docker.compose.project=' . $composerName;
        // return static::$dcDir.$app.' ps';
    }

    public function down($app)
    {
        return static::$dcDir . $app . ' stop && exit';
    }

    public function tail($app)
    {
        return static::$dcDir . $app . ' logs && exit';
    }

    public function restart($app)
    {
        return static::$dcDir . $app . ' restart && exit';
    }

    public function exec($app)
    {
        return 'title=' . $app . ' tilix -a app-new-session --quake -x \"docker exec -it ' . $app . ' bash\" || tilix -x \"docker exec -it ' . $app . ' sh\"';
    }

    public function up($app)
    {
        return static::$dcDir . $app . ' start && exit';
    }

    public function url($app)
    {
    		$appurl = trim(exec(static::$dcDir . $app . ' env | grep "App URL"'));
    		return array_reverse(explode(' ', str_replace('App URL:', '', $appurl)))[0];
    }

    public function serving()
    {
        $processes = [];
        exec('curl https://ping.avoid.work/.status -AChrome --connect-timeout 2 -s | grep Active', $processes);

        return strtolower(trim(join(', ', $processes)));
    }
}

new DockerApps;
