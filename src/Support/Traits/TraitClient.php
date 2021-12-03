<?php
declare (strict_types=1);

namespace Intoy\HebatSupport\Traits;

use Psr\Http\Message\ServerRequestInterface as Request;

trait TraitClient  
{
    /**
     * @return string 
     */
    protected static function getClientIpFromEnv()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');        
        return $ipaddress;
    }


    /**
     * @param array $server from $request->getServerParams()
     * @return string 
     */
    protected static function getClientIpFromServerParams(array $server)
    {
        $ipaddress = '';
        $fill=[
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach($fill as $f)
        {
            if(isset($server[$f]))
            {
                $ipaddress=$server[$f];
                break;
            }
        }            
        return $ipaddress;
    }

    /**
     * @param Request $request
     * @return string|null
     */
    protected static function getClientIpFromRequest(Request $request)
    {
        $server=$request->getServerParams();
        $ip=static::getClientIpFromServerParams($server);
        $true=filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false;
        if($true) return $ip;
        return null;
    }
}