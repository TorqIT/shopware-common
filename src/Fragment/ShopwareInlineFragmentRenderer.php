<?php declare(strict_types=1);

namespace  Torq\Shopware\Common\Fragment;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;

class ShopwareInlineFragmentRenderer extends InlineFragmentRenderer
{
    private array $options = [];
    /**
     * Fixing an issue when render() is used in a twig template.  When the subrequest was created the 
     * sw token was not getting carried over which caused the Customer object in the session to be empty. 
     * 
     * @param string $uri 
     * @param Request $request 
     * @return Request 
     * @throws BadRequestException 
     * @throws InvalidArgumentException 
     */
    protected function createSubRequest(string $uri, Request $request): Request{
        $subRequest = parent::createSubRequest($uri,$request);

        if($this->options && array_key_exists('rParams', $this->options)){
            $params = explode('&', $this->options['rParams']);

            foreach($params as $param){
                $param = explode('=', $param);
                $subRequest->attributes->set($param[0], $param[1]);
            }
        }

        $subRequest->headers->set("sw-context-token", $request->headers->get("sw-context-token"));
        return $subRequest;
    }

    public function render(string|ControllerReference $uri, Request $request, array $options = []): Response
    {
        $this->options = $options;
        $response = parent::render($uri, $request, $options);
        return $response;
    }
}