<?php

namespace Tsugi\Lumen;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tsugi\UI\Output;

/**
 * Exception handler for the Application
 */
class ExceptionHandler
{
    /**
     * Exceptions that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [];

    /**
     * Indicate an exception that should not be reported
     *
     * @param  string  $type
     * @return void
     */
    public function pleaseDontReport($type)
    {
        if ( ! in_array($type, $this->dontReport) ) {
            $this->dontReport[] = $type;
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function render($request, \Throwable $e)
    {
        global $CFG, $OUTPUT;
        
        if ($e instanceof NotFoundHttpException) {
            $OUTPUT->buffer = true;
            $txt = $OUTPUT->header();
            $txt .= $OUTPUT->bodyStart();
            $txt .= $OUTPUT->topNav();
            $txt .= '<h1>';
            $txt .= __('Not Found');
            $txt .= '</h1>';
            $txt .= $OUTPUT->footer();
            return new Response($txt, 404);
        }

        // For other exceptions, re-throw or handle as needed
        throw $e;
    }
}
