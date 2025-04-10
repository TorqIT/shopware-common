<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Twig;

use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class TwigExtensions extends AbstractExtension
{
    public function __construct()
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('json_decode', [$this, 'jsonDecode'])
        ];
    }

    /**
     * Uses PHP json_decode to create a an array from a json string
     * 
     * @param string $str 
     * @return mixed 
     */
    public function jsonDecode(string $str){
        $arr = json_decode($str,true);
        return $arr;
    }
    
}