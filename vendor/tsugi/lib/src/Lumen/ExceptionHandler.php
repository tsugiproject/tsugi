<?php

namespace Tsugi\Lumen;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tsugi\UI\Output;

// I wrote this stack overflow and then after I wrote it I figured out the answer
// https://stackoverflow.com/questions/65777054/how-do-i-add-a-custom-404-page-to-a-lumen-not-laravel-application

class ExceptionHandler extends \Laravel\Lumen\Exceptions\Handler {

    // TODO: figure out why we could not set
    // protected $dontReport = [];
    // in a constructor here.  Instead we override report() below
    // because it works.

    /**
     * Override report to eat our 404's
     *
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    public function report(\Exception $e)
    {
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException ) return;
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function render($request, \Exception $e)
    {
        global $CFG, $OUTPUT;
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException ) {
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

        return parent::render($request, $e);
    }
}

