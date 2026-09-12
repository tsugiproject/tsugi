<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Tsugi\Services\Quiz1\Answer;
use Tsugi\Services\Quiz1\ExportException;
use Tsugi\Services\Quiz1\GiftExporter;
use Tsugi\Services\Quiz1\GiftImporter;
use Tsugi\Services\Quiz1\Grader;
use Tsugi\Services\Quiz1\Html;
use Tsugi\Services\Quiz1\ImportException;
use Tsugi\Services\Quiz1\Qti12Exporter;
use Tsugi\Services\Quiz1\Qti12Importer;
use Tsugi\Services\Quiz1\Question;
use Tsugi\Services\Quiz1\QuestionTypes;
use Tsugi\Services\Quiz1\Quiz;
use Tsugi\Services\Quiz1\QuizRepository;
use Tsugi\Services\Quiz1\SampleQuiz;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class Quiz1 extends Tool {

    const ROUTE = '/quiz1';
    const NAME = 'Quizzes';
    const REDIRECT = 'tsugi_controllers_quiz1';
    const FORM_SESSION = 'quiz1_form';

    /**
     * True when the current user may author quizzes (instructor/admin).
     *
     * Parent menus:
     * if ( \Tsugi\Controllers\Quiz1::showInMenu() ) {
     *     $set->addLeft('Quizzes', rtrim($CFG->apphome, '/') . \Tsugi\Controllers\Courses::toolPathPrefix() . '/quiz1');
     * }
     */
    public static function showInMenu() {
        $context_id = U::currentContextId();
        $user_id = U::loggedInUserId();
        if ( ! $context_id || ! $user_id ) {
            return false;
        }
        $tool = new self();
        return $tool->isInstructor();
    }

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Quiz1@index');
        $app->router->get($prefix.'/', 'Quiz1@index');
        $app->router->get('/'.self::REDIRECT, 'Quiz1@index');
        $app->router->get($prefix.'/add', 'Quiz1@add');
        $app->router->post($prefix.'/add', 'Quiz1@addPost');
        $app->router->post($prefix.'/sample', 'Quiz1@samplePost');
        $app->router->get($prefix.'/{id}/export', 'Quiz1@export');
        $app->router->get($prefix.'/{id}/export/gift', 'Quiz1@exportGift');
        $app->router->get($prefix.'/{id}/import/gift', 'Quiz1@importGift');
        $app->router->post($prefix.'/{id}/import/gift', 'Quiz1@importGiftPost');
        $app->router->get($prefix.'/{id}/import/qti', 'Quiz1@importQti');
        $app->router->post($prefix.'/{id}/import/qti', 'Quiz1@importQtiPost');
        $app->router->get($prefix.'/{id}/questions/add', 'Quiz1@questionAdd');
        $app->router->post($prefix.'/{id}/questions/add', 'Quiz1@questionAddPost');
        $app->router->get($prefix.'/{id}/questions/{qid}/edit', 'Quiz1@questionEdit');
        $app->router->post($prefix.'/{id}/questions/{qid}/edit', 'Quiz1@questionEditPost');
        $app->router->post($prefix.'/{id}/questions/{qid}/delete', 'Quiz1@questionDeletePost');
        $app->router->post($prefix.'/{id}/questions/{qid}/move', 'Quiz1@questionMovePost');
        $app->router->get($prefix.'/{id}/edit', 'Quiz1@edit');
        $app->router->post($prefix.'/{id}/edit', 'Quiz1@editPost');
        $app->router->post($prefix.'/{id}/delete', 'Quiz1@deletePost');
        $app->router->get($prefix.'/{id}', 'Quiz1@take');
        $app->router->post($prefix.'/{id}', 'Quiz1@takePost');
    }

    public function index(Request $request) {
        global $OUTPUT;

        $this->requireAuth();
        LTIX::getConnection();

        $context_id = U::currentContextId();
        $quizzes = QuizRepository::listForContext($context_id);
        $home = $this->toolHome(self::ROUTE);

        if ( ! $this->isInstructor() ) {
            $this->renderStudentIndex($quizzes, $home);
            return;
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" role="main" id="main-content">
            <h1><?= htmlspecialchars(__('Quizzes')) ?>
                <span class="pull-right">
                    <a href="<?= htmlspecialchars($home.'/add') ?>" class="btn btn-primary"><?= htmlspecialchars(__('New Quiz')) ?></a>
                </span>
            </h1>
            <p class="help-block"><?= htmlspecialchars(__('Create quizzes as course resources. Students take them from Lessons or from the Take link. Import or export GIFT and Common Cartridge QTI 1.2.1 on any quiz.')) ?></p>
            <?php if ( count($quizzes) < 1 ): ?>
                <p><?= htmlspecialchars(__('No quizzes yet.')) ?></p>
                <form method="post" action="<?= htmlspecialchars($home.'/sample') ?>">
                    <?= self::csrfField() ?>
                    <button type="submit" class="btn btn-default"><?= htmlspecialchars(__('Create sample quiz (all question types)')) ?></button>
                </form>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars(__('Title')) ?></th>
                            <th><?= htmlspecialchars(__('Questions')) ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $quizzes as $quiz ): ?>
                        <tr>
                            <td><?= htmlspecialchars($quiz->title) ?></td>
                            <td><?= (int) $quiz->question_count ?></td>
                            <td class="text-right">
                                <a class="btn btn-xs btn-primary" href="<?= htmlspecialchars($home.'/'.$quiz->id) ?>"><?= htmlspecialchars(__('Take')) ?></a>
                                <a class="btn btn-xs btn-default" href="<?= htmlspecialchars($home.'/'.$quiz->id.'/edit') ?>"><?= htmlspecialchars(__('Edit')) ?></a>
                                <?= self::interchangeButtons($home, $quiz->id, true) ?>
                                <form method="post" action="<?= htmlspecialchars($home.'/'.$quiz->id.'/delete') ?>" style="display:inline;" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Delete this quiz and all of its questions?')), ENT_QUOTES) ?>);">
                                    <?= self::csrfField() ?>
                                    <button type="submit" class="btn btn-xs btn-danger"><?= htmlspecialchars(__('Delete')) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="post" action="<?= htmlspecialchars($home.'/sample') ?>">
                    <?= self::csrfField() ?>
                    <button type="submit" class="btn btn-default"><?= htmlspecialchars(__('Create sample quiz (all question types)')) ?></button>
                </form>
            <?php endif; ?>
        </main>
        <?php
        $OUTPUT->footer();
    }

    public function add(Request $request) {
        $this->requireInstructor($this->toolHome(self::ROUTE));
        $quiz = new Quiz();
        $saved = $this->takeForm('quiz_add');
        if ( $saved ) {
            $quiz->title = U::get($saved, 'title', '');
            $quiz->instructions = U::get($saved, 'instructions', '');
        }
        $this->renderQuizForm($quiz, $this->toolHome(self::ROUTE).'/add', __('New Quiz'), true);
    }

    public function addPost(Request $request) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $csrf = self::requireCsrf($home.'/add');
        if ( $csrf ) {
            return $csrf;
        }

        $quiz = new Quiz();
        $quiz->context_id = U::currentContextId();
        $quiz->user_id = U::loggedInUserId();
        $quiz->title = trim(U::get($_POST, 'title', ''));
        $quiz->instructions = Html::purify(U::get($_POST, 'instructions', ''));
        $errors = $quiz->validate();
        if ( count($errors) ) {
            foreach ( $errors as $err ) {
                U::flashError($err);
            }
            $this->stashForm(array(
                'kind' => 'quiz_add',
                'title' => $quiz->title,
                'instructions' => $quiz->instructions,
            ));
            return new RedirectResponse($home.'/add');
        }
        $id = QuizRepository::insertQuiz($quiz);
        unset($_SESSION[self::FORM_SESSION]);
        U::flashSuccess(__('Quiz created.'));
        return new RedirectResponse($home.'/'.$id.'/edit');
    }

    public function edit(Request $request, $id) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        if ( ! $quiz ) {
            U::flashError(__('Quiz not found.'));
            return new RedirectResponse($home);
        }
        $saved = $this->takeForm('quiz_edit', $quiz->id);
        if ( $saved ) {
            $quiz->title = U::get($saved, 'title', '');
            $quiz->instructions = U::get($saved, 'instructions', '');
        }
        $this->renderQuizEdit($quiz);
    }

    public function editPost(Request $request, $id) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $csrf = self::requireCsrf($home.'/'.$id.'/edit');
        if ( $csrf ) {
            return $csrf;
        }
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        if ( ! $quiz ) {
            U::flashError(__('Quiz not found.'));
            return new RedirectResponse($home);
        }
        $quiz->title = trim(U::get($_POST, 'title', ''));
        $quiz->instructions = Html::purify(U::get($_POST, 'instructions', ''));
        $errors = $quiz->validate();
        if ( count($errors) ) {
            foreach ( $errors as $err ) {
                U::flashError($err);
            }
            $this->stashForm(array(
                'kind' => 'quiz_edit',
                'quiz_id' => (int) $quiz->id,
                'title' => $quiz->title,
                'instructions' => $quiz->instructions,
            ));
            return new RedirectResponse($home.'/'.$id.'/edit');
        }
        QuizRepository::updateQuizMeta($quiz);
        unset($_SESSION[self::FORM_SESSION]);
        U::flashSuccess(__('Quiz updated.'));
        return new RedirectResponse($home.'/'.$id.'/edit');
    }

    public function deletePost(Request $request, $id) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $csrf = self::requireCsrf($home);
        if ( $csrf ) {
            return $csrf;
        }
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        if ( ! $quiz ) {
            U::flashError(__('Quiz not found.'));
            return new RedirectResponse($home);
        }
        QuizRepository::deleteQuiz((int) $id, U::currentContextId());
        U::flashSuccess(__('Quiz deleted.'));
        return new RedirectResponse($home);
    }

    public function take(Request $request, $id) {
        return $this->renderTake((int) $id, null);
    }

    public function takePost(Request $request, $id) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireAuth();
        $csrf = self::requireCsrf($home.'/'.$id);
        if ( $csrf ) {
            return $csrf;
        }
        LTIX::getConnection();
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        if ( ! $quiz ) {
            U::flashError(__('Quiz not found.'));
            return new RedirectResponse($home);
        }
        $result = Grader::grade($quiz, $_POST);
        return $this->renderTake((int) $id, $result, $quiz);
    }

    /**
     * @param array{earned:int,possible:int,essay_possible:int,items:array}|null $result
     */
    private function renderTake($id, $result, $quiz = null) {
        global $OUTPUT;

        $home = $this->toolHome(self::ROUTE);
        $this->requireAuth();
        LTIX::getConnection();
        if ( $quiz === null ) {
            $quiz = QuizRepository::load((int) $id, U::currentContextId());
        }
        if ( ! $quiz ) {
            U::flashError(__('Quiz not found.'));
            return new RedirectResponse($home);
        }

        $lessons_url = U::addSession($this->toolHome(\Tsugi\Controllers\Lessons::ROUTE));
        $can_edit = $this->isInstructor();

        $OUTPUT->header();
        $OUTPUT->bodyStart($result === null);
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        include __DIR__ . '/templates/Quiz1/take.inc.php';
        $OUTPUT->footer();
        return '';
    }

    /**
     * @param \Tsugi\Services\Quiz1\Quiz[] $quizzes
     */
    private function renderStudentIndex(array $quizzes, $home) {
        global $OUTPUT;
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" role="main" id="main-content">
            <h1><?= htmlspecialchars(__('Quizzes')) ?></h1>
            <?php if ( count($quizzes) < 1 ): ?>
                <p><?= htmlspecialchars(__('No quizzes in this course yet.')) ?></p>
            <?php else: ?>
                <ul class="list-unstyled">
                <?php foreach ( $quizzes as $quiz ): ?>
                    <li style="margin: 0.5em 0;">
                        <a href="<?= htmlspecialchars($home.'/'.$quiz->id) ?>"><?= htmlspecialchars($quiz->title) ?></a>
                        (<?= (int) $quiz->question_count ?> <?= htmlspecialchars(__('questions')) ?>)
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </main>
        <?php
        $OUTPUT->footer();
    }

    public function questionAdd(Request $request, $id) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        if ( ! $quiz ) {
            U::flashError(__('Quiz not found.'));
            return new RedirectResponse($home);
        }
        $saved = $this->takeForm('question_add', $quiz->id);
        if ( $saved ) {
            $question = $this->questionFromFormState($saved);
            $question->quiz_id = $quiz->id;
        } else {
            $question = new Question();
            $question->quiz_id = $quiz->id;
            $question->type = U::get($_GET, 'type', QuestionTypes::MULTIPLE_CHOICE);
            if ( ! QuestionTypes::isValid($question->type) ) {
                $question->type = QuestionTypes::MULTIPLE_CHOICE;
            }
            $this->seedDefaultAnswers($question);
        }
        $this->renderQuestionForm($quiz, $question, $home.'/'.$quiz->id.'/questions/add', true);
    }

    public function questionAddPost(Request $request, $id) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $csrf = self::requireCsrf($home.'/'.$id.'/questions/add');
        if ( $csrf ) {
            return $csrf;
        }
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        if ( ! $quiz ) {
            U::flashError(__('Quiz not found.'));
            return new RedirectResponse($home);
        }
        $question = $this->questionFromPost();
        $question->quiz_id = $quiz->id;
        $errors = $question->validate();
        if ( count($errors) ) {
            foreach ( $errors as $err ) {
                U::flashError($err);
            }
            $this->stashForm($this->questionFormState('question_add', $question, $quiz->id));
            return new RedirectResponse($home.'/'.$quiz->id.'/questions/add?type='.rawurlencode($question->type));
        }
        QuizRepository::insertQuestion($question);
        unset($_SESSION[self::FORM_SESSION]);
        U::flashSuccess(__('Question added.'));
        return new RedirectResponse($home.'/'.$quiz->id.'/edit');
    }

    public function questionEdit(Request $request, $id, $qid) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        $question = QuizRepository::loadQuestion((int) $qid, U::currentContextId());
        if ( ! $quiz || ! $question || (int) $question->quiz_id !== (int) $quiz->id ) {
            U::flashError(__('Question not found.'));
            return new RedirectResponse($home);
        }
        $saved = $this->takeForm('question_edit', $quiz->id, $question->id);
        if ( $saved ) {
            $restored = $this->questionFromFormState($saved);
            $restored->id = $question->id;
            $restored->quiz_id = $question->quiz_id;
            $restored->sequence = $question->sequence;
            $restored->type = $question->type;
            $question = $restored;
        }
        $this->renderQuestionForm($quiz, $question, $home.'/'.$quiz->id.'/questions/'.$question->id.'/edit', false);
    }

    public function questionEditPost(Request $request, $id, $qid) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $csrf = self::requireCsrf($home.'/'.$id.'/questions/'.$qid.'/edit');
        if ( $csrf ) {
            return $csrf;
        }
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        $existing = QuizRepository::loadQuestion((int) $qid, U::currentContextId());
        if ( ! $quiz || ! $existing || (int) $existing->quiz_id !== (int) $quiz->id ) {
            U::flashError(__('Question not found.'));
            return new RedirectResponse($home);
        }
        $question = $this->questionFromPost();
        $question->id = $existing->id;
        $question->quiz_id = $existing->quiz_id;
        $question->sequence = $existing->sequence;
        $question->type = $existing->type;
        $errors = $question->validate();
        if ( count($errors) ) {
            foreach ( $errors as $err ) {
                U::flashError($err);
            }
            $this->stashForm($this->questionFormState('question_edit', $question, $quiz->id));
            return new RedirectResponse($home.'/'.$quiz->id.'/questions/'.$qid.'/edit');
        }
        QuizRepository::updateQuestion($question);
        unset($_SESSION[self::FORM_SESSION]);
        U::flashSuccess(__('Question updated.'));
        return new RedirectResponse($home.'/'.$quiz->id.'/edit');
    }

    public function questionDeletePost(Request $request, $id, $qid) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $csrf = self::requireCsrf($home.'/'.$id.'/edit');
        if ( $csrf ) {
            return $csrf;
        }
        if ( ! QuizRepository::deleteQuestion((int) $qid, U::currentContextId()) ) {
            U::flashError(__('Question not found.'));
        } else {
            U::flashSuccess(__('Question deleted.'));
        }
        return new RedirectResponse($home.'/'.$id.'/edit');
    }

    public function questionMovePost(Request $request, $id, $qid) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $csrf = self::requireCsrf($home.'/'.$id.'/edit');
        if ( $csrf ) {
            return $csrf;
        }
        $dir = U::get($_POST, 'direction', '');
        if ( $dir !== 'up' && $dir !== 'down' ) {
            U::flashError(__('Invalid move direction.'));
            return new RedirectResponse($home.'/'.$id.'/edit');
        }
        QuizRepository::moveQuestion((int) $qid, U::currentContextId(), $dir);
        return new RedirectResponse($home.'/'.$id.'/edit');
    }

    public function export(Request $request, $id) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        if ( ! $quiz ) {
            U::flashError(__('Quiz not found.'));
            return new RedirectResponse($home);
        }
        try {
            $xml = Qti12Exporter::export($quiz);
        } catch ( ExportException $e ) {
            U::flashError($e->getMessage());
            return new RedirectResponse($home.'/'.$id.'/edit');
        }
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($quiz->title));
        $slug = trim($slug, '-');
        if ( $slug === '' ) {
            $slug = 'quiz-'.$quiz->id;
        }
        $response = new Response($xml, 200, array(
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$slug.'-qti.xml"',
        ));
        return $response;
    }

    public function exportGift(Request $request, $id) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        if ( ! $quiz ) {
            U::flashError(__('Quiz not found.'));
            return new RedirectResponse($home);
        }
        try {
            $gift = GiftExporter::export($quiz);
        } catch ( ExportException $e ) {
            U::flashError($e->getMessage());
            return new RedirectResponse($home.'/'.$id.'/edit');
        }
        $response = new Response($gift, 200, array(
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->quizSlug($quiz).'.gift"',
        ));
        return $response;
    }

    public function importGift(Request $request, $id) {
        return $this->renderImportForm((int) $id, 'gift');
    }

    public function importGiftPost(Request $request, $id) {
        return $this->handleImportPost((int) $id, 'gift');
    }

    public function importQti(Request $request, $id) {
        return $this->renderImportForm((int) $id, 'qti');
    }

    public function importQtiPost(Request $request, $id) {
        return $this->handleImportPost((int) $id, 'qti');
    }

    public function samplePost(Request $request) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $csrf = self::requireCsrf($home);
        if ( $csrf ) {
            return $csrf;
        }
        $quiz = SampleQuiz::build(0);
        $quiz->id = null;
        foreach ( $quiz->questions as $q ) {
            $q->id = null;
            $q->quiz_id = null;
            foreach ( $q->answers as $a ) {
                $a->id = null;
            }
        }
        $quiz->context_id = U::currentContextId();
        $quiz->user_id = U::loggedInUserId();
        $id = QuizRepository::insertQuiz($quiz);
        U::flashSuccess(__('Sample quiz created. Export QTI or GIFT, or import more questions from GIFT or QTI.'));
        return new RedirectResponse($home.'/'.$id.'/edit');
    }

    /**
     * Import / export actions available on every quiz.
     */
    private static function interchangeButtons($home, $quiz_id, $small) {
        $cls = $small ? 'btn btn-xs btn-default' : 'btn btn-default';
        $id = (int) $quiz_id;
        ob_start();
        ?>
        <a class="<?= $cls ?>" href="<?= htmlspecialchars($home.'/'.$id.'/export') ?>"><?= htmlspecialchars(__('Export QTI')) ?></a>
        <a class="<?= $cls ?>" href="<?= htmlspecialchars($home.'/'.$id.'/export/gift') ?>"><?= htmlspecialchars(__('Export GIFT')) ?></a>
        <a class="<?= $cls ?>" href="<?= htmlspecialchars($home.'/'.$id.'/import/gift') ?>"><?= htmlspecialchars(__('Import GIFT')) ?></a>
        <a class="<?= $cls ?>" href="<?= htmlspecialchars($home.'/'.$id.'/import/qti') ?>"><?= htmlspecialchars(__('Import QTI')) ?></a>
        <?php
        return trim(ob_get_clean());
    }

    private function quizSlug(Quiz $quiz) {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($quiz->title));
        $slug = trim($slug, '-');
        if ( $slug === '' ) {
            $slug = 'quiz-'.$quiz->id;
        }
        return $slug;
    }

    /**
     * @param 'gift'|'qti' $format
     */
    private function renderImportForm($id, $format) {
        global $OUTPUT;
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        if ( ! $quiz ) {
            U::flashError(__('Quiz not found.'));
            return new RedirectResponse($home);
        }

        $is_gift = $format === 'gift';
        $heading = $is_gift ? __('Import GIFT') : __('Import QTI');
        $action = $home.'/'.$quiz->id.'/import/'.($is_gift ? 'gift' : 'qti');
        $accept = $is_gift ? '.gift,.txt,text/plain' : '.xml,.zip,.imscc,application/xml,text/xml,application/zip';
        $help = $is_gift
            ? __('Paste Moodle GIFT or upload a .gift / .txt file. Questions are added to this quiz. Numerical and matching items are skipped.')
            : __('Paste Common Cartridge QTI 1.2.1 XML or upload an .xml / .zip / .imscc file. Questions are added to this quiz.');

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" role="main" id="main-content">
            <p><a href="<?= htmlspecialchars($home.'/'.$quiz->id.'/edit') ?>">&larr; <?= htmlspecialchars($quiz->title) ?></a></p>
            <h1><?= htmlspecialchars($heading) ?></h1>
            <p class="help-block"><?= htmlspecialchars($help) ?></p>
            <form method="post" action="<?= htmlspecialchars($action) ?>" enctype="multipart/form-data">
                <?= self::csrfField() ?>
                <div class="form-group">
                    <label for="file"><?= htmlspecialchars(__('File')) ?></label>
                    <input type="file" id="file" name="file" accept="<?= htmlspecialchars($accept) ?>">
                </div>
                <div class="form-group">
                    <label for="text"><?= htmlspecialchars($is_gift ? __('Or paste GIFT') : __('Or paste QTI XML')) ?></label>
                    <textarea class="form-control" id="text" name="text" rows="16" style="font-family:monospace;"></textarea>
                </div>
                <p>
                    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('Import questions')) ?></button>
                    <a class="btn btn-default" href="<?= htmlspecialchars($home.'/'.$quiz->id.'/edit') ?>"><?= htmlspecialchars(__('Cancel')) ?></a>
                </p>
            </form>
        </main>
        <?php
        $OUTPUT->footer();
        return '';
    }

    /**
     * @param 'gift'|'qti' $format
     */
    private function handleImportPost($id, $format) {
        $home = $this->toolHome(self::ROUTE);
        $this->requireInstructor($home);
        $csrf = self::requireCsrf($home.'/'.$id.'/import/'.$format);
        if ( $csrf ) {
            return $csrf;
        }
        $quiz = QuizRepository::load((int) $id, U::currentContextId());
        if ( ! $quiz ) {
            U::flashError(__('Quiz not found.'));
            return new RedirectResponse($home);
        }

        try {
            $payload = $this->readImportInput();
            if ( $format === 'gift' ) {
                list($imported, $warnings) = GiftImporter::import($payload);
            } else {
                list($imported, $warnings) = Qti12Importer::import($payload);
            }
        } catch ( ImportException $e ) {
            U::flashError($e->getMessage());
            return new RedirectResponse($home.'/'.$id.'/import/'.$format);
        }

        $n = QuizRepository::appendQuestions($quiz->id, $imported->questions);
        if ( $n < 1 ) {
            U::flashError(__('No questions were imported.'));
            return new RedirectResponse($home.'/'.$id.'/import/'.$format);
        }
        U::flashSuccess(sprintf(__('Imported %d question(s).'), $n));
        foreach ( $warnings as $warn ) {
            U::flashError($warn);
        }
        return new RedirectResponse($home.'/'.$id.'/edit');
    }

    private function readImportInput() {
        if ( ! empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']) ) {
            if ( (int) $_FILES['file']['error'] !== UPLOAD_ERR_OK ) {
                throw new ImportException(__('File upload failed.'));
            }
            if ( (int) $_FILES['file']['size'] > 2 * 1024 * 1024 ) {
                throw new ImportException(__('File is too large (2 MB maximum).'));
            }
            $bytes = file_get_contents($_FILES['file']['tmp_name']);
            if ( $bytes === false || $bytes === '' ) {
                throw new ImportException(__('The uploaded file is empty.'));
            }
            return $bytes;
        }
        $text = trim((string) U::get($_POST, 'text', ''));
        if ( $text === '' ) {
            throw new ImportException(__('Paste quiz text or choose a file.'));
        }
        return $text;
    }

    /**
     * One-slot flash of submitted fields after a validation redirect.
     * Always overwrites; never accumulates. Stamped with course/quiz/question.
     *
     * @param array<string, mixed> $data
     */
    private function stashForm(array $data) {
        $data['context_id'] = U::currentContextId();
        $data['quiz_id'] = (int) U::get($data, 'quiz_id', 0);
        $data['question_id'] = (int) U::get($data, 'question_id', 0);
        $_SESSION[self::FORM_SESSION] = $data;
    }

    /**
     * Consume the form flash. Any Quiz1 form GET takes the slot: restore on
     * full match (course, kind, quiz, question), otherwise discard. Refresh
     * and other tabs/courses lose the draft rather than leaking it.
     *
     * @return array<string, mixed>|null
     */
    private function takeForm($kind, $quiz_id = 0, $question_id = 0) {
        $data = isset($_SESSION[self::FORM_SESSION]) && is_array($_SESSION[self::FORM_SESSION])
            ? $_SESSION[self::FORM_SESSION]
            : null;
        unset($_SESSION[self::FORM_SESSION]);
        if ( ! $data ) {
            return null;
        }
        if ( (int) U::get($data, 'context_id', 0) !== U::currentContextId() ) {
            return null;
        }
        if ( U::get($data, 'kind') !== $kind ) {
            return null;
        }
        if ( (int) U::get($data, 'quiz_id', 0) !== (int) $quiz_id ) {
            return null;
        }
        if ( (int) U::get($data, 'question_id', 0) !== (int) $question_id ) {
            return null;
        }
        return $data;
    }

    /**
     * @param int|string $quiz_id
     * @return array<string, mixed>
     */
    private function questionFormState($kind, Question $question, $quiz_id) {
        $answers = array();
        foreach ( $question->answers as $ans ) {
            $answers[] = array(
                'text' => $ans->text,
                'correct' => $ans->correct ? true : false,
                'sequence' => (int) $ans->sequence,
            );
        }
        return array(
            'kind' => $kind,
            'quiz_id' => (int) $quiz_id,
            'question_id' => (int) $question->id,
            'type' => $question->type,
            'title' => $question->title,
            'prompt' => $question->prompt,
            'points' => (int) $question->points,
            'feedback' => $question->feedback,
            'sample_solution' => $question->sample_solution,
            'case_sensitive' => $question->case_sensitive ? true : false,
            'answers' => $answers,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function questionFromFormState(array $data) {
        $q = new Question();
        $q->type = (string) U::get($data, 'type', '');
        $q->title = (string) U::get($data, 'title', '');
        $q->prompt = (string) U::get($data, 'prompt', '');
        $q->points = (int) U::get($data, 'points', 1);
        $q->feedback = (string) U::get($data, 'feedback', '');
        $q->sample_solution = (string) U::get($data, 'sample_solution', '');
        $q->case_sensitive = U::get($data, 'case_sensitive') ? true : false;
        $rows = U::get($data, 'answers', array());
        if ( ! is_array($rows) ) {
            $rows = array();
        }
        $seq = 1;
        foreach ( $rows as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $q->answers[] = Answer::make(
                U::get($row, 'text', ''),
                U::get($row, 'correct') ? true : false,
                (int) U::get($row, 'sequence', $seq)
            );
            $seq++;
        }
        return $q;
    }

    private function questionFromPost() {
        $q = new Question();
        $q->type = U::get($_POST, 'qtype', '');
        $q->title = trim(U::get($_POST, 'title', ''));
        $q->prompt = Html::purify(U::get($_POST, 'prompt', ''));
        $q->points = U::get($_POST, 'points', 1);
        $q->feedback = Html::purify(U::get($_POST, 'feedback', ''));
        $q->sample_solution = Html::purify(U::get($_POST, 'sample_solution', ''));
        $q->case_sensitive = U::get($_POST, 'case_sensitive') ? true : false;

        if ( $q->type === QuestionTypes::TRUE_FALSE ) {
            $correct = U::get($_POST, 'tf_correct', 'true') === 'true';
            $q->answers = array(
                Answer::make('True', $correct, 1),
                Answer::make('False', ! $correct, 2),
            );
            return $q;
        }

        if ( $q->type === QuestionTypes::ESSAY ) {
            $q->answers = array();
            return $q;
        }

        $texts = U::get($_POST, 'answer_text', array());
        if ( ! is_array($texts) ) {
            $texts = array();
        }
        $corrects = U::get($_POST, 'answer_correct', array());
        if ( ! is_array($corrects) ) {
            $corrects = array();
        }
        $mc_correct = U::get($_POST, 'mc_correct', '');

        $seq = 1;
        foreach ( $texts as $i => $text ) {
            $text = Html::purify($text);
            if ( Question::isBlankHtml($text) ) {
                continue;
            }
            $correct = false;
            if ( $q->type === QuestionTypes::MULTIPLE_CHOICE ) {
                $correct = ((string) $mc_correct === (string) $i);
            } else if ( $q->type === QuestionTypes::MULTIPLE_RESPONSE ) {
                $correct = ! empty($corrects[$i]);
            } else {
                $correct = true;
            }
            $q->answers[] = Answer::make($text, $correct, $seq);
            $seq++;
        }
        return $q;
    }

    private function seedDefaultAnswers(Question $question) {
        if ( $question->type === QuestionTypes::TRUE_FALSE ) {
            $question->answers = array(
                Answer::make('True', true, 1),
                Answer::make('False', false, 2),
            );
            return;
        }
        if ( QuestionTypes::usesChoiceAnswers($question->type) ) {
            for ( $i = 1; $i <= 4; $i++ ) {
                $question->answers[] = Answer::make('', $i === 1, $i);
            }
            return;
        }
        if ( QuestionTypes::usesAcceptedStrings($question->type) ) {
            $question->answers[] = Answer::make('', true, 1);
        }
    }

    private function renderQuizForm(Quiz $quiz, $action, $heading, $is_new) {
        global $OUTPUT;
        $home = $this->toolHome(self::ROUTE);
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" role="main" id="main-content">
            <h1><?= htmlspecialchars($heading) ?></h1>
            <form method="post" action="<?= htmlspecialchars($action) ?>" id="quiz_form">
                <?= self::csrfField() ?>
                <div class="form-group">
                    <label for="title"><?= htmlspecialchars(__('Title')) ?></label>
                    <input type="text" class="form-control" id="title" name="title" required
                           value="<?= htmlspecialchars($quiz->title) ?>">
                </div>
                <div class="form-group">
                    <label for="editor_instructions"><?= htmlspecialchars(__('Instructions')) ?></label>
                    <div class="ckeditor-container">
                        <textarea name="instructions" id="editor_instructions"><?= htmlspecialchars($quiz->instructions) ?></textarea>
                    </div>
                </div>
                <p>
                    <button type="submit" class="btn btn-primary"><?= htmlspecialchars($is_new ? __('Create Quiz') : __('Save')) ?></button>
                    <a class="btn btn-default" href="<?= htmlspecialchars($home) ?>"><?= htmlspecialchars(__('Cancel')) ?></a>
                </p>
            </form>
        </main>
        <?php
        $this->renderEditorFooter(array('editor_instructions'));
    }

    private function renderQuizEdit(Quiz $quiz) {
        global $OUTPUT;
        $home = $this->toolHome(self::ROUTE);
        $questions = $quiz->orderedQuestions();
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" role="main" id="main-content">
            <p><a href="<?= htmlspecialchars($home) ?>">&larr; <?= htmlspecialchars(__('All quizzes')) ?></a></p>
            <h1><?= htmlspecialchars(__('Edit Quiz')) ?>
                <span class="pull-right">
                    <a class="btn btn-primary" href="<?= htmlspecialchars($home.'/'.$quiz->id) ?>"><?= htmlspecialchars(__('Take quiz')) ?></a>
                    <?= self::interchangeButtons($home, $quiz->id, false) ?>
                </span>
            </h1>
            <form method="post" action="<?= htmlspecialchars($home.'/'.$quiz->id.'/edit') ?>" id="quiz_form">
                <?= self::csrfField() ?>
                <div class="form-group">
                    <label for="title"><?= htmlspecialchars(__('Title')) ?></label>
                    <input type="text" class="form-control" id="title" name="title" required
                           value="<?= htmlspecialchars($quiz->title) ?>">
                </div>
                <div class="form-group">
                    <label for="editor_instructions"><?= htmlspecialchars(__('Instructions')) ?></label>
                    <div class="ckeditor-container">
                        <textarea name="instructions" id="editor_instructions"><?= htmlspecialchars($quiz->instructions) ?></textarea>
                    </div>
                </div>
                <p>
                    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('Save quiz')) ?></button>
                </p>
            </form>

            <h2><?= htmlspecialchars(__('Questions')) ?></h2>
            <p>
                <?php foreach ( QuestionTypes::labels() as $type => $label ): ?>
                    <a class="btn btn-default btn-sm" href="<?= htmlspecialchars($home.'/'.$quiz->id.'/questions/add?type='.rawurlencode($type)) ?>"><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>
            </p>
            <?php if ( count($questions) < 1 ): ?>
                <p><?= htmlspecialchars(__('No questions yet. Add one of the six Common Cartridge question types above.')) ?></p>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= htmlspecialchars(__('Type')) ?></th>
                            <th><?= htmlspecialchars(__('Question')) ?></th>
                            <th><?= htmlspecialchars(__('Points')) ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $questions as $q ): ?>
                        <tr>
                            <td><?= (int) $q->sequence ?></td>
                            <td><?= htmlspecialchars(QuestionTypes::label($q->type)) ?></td>
                            <td><?= htmlspecialchars($q->title !== '' ? $q->title : Html::excerpt($q->prompt)) ?></td>
                            <td><?= (int) $q->points ?></td>
                            <td class="text-right" style="white-space:nowrap;">
                                <form method="post" action="<?= htmlspecialchars($home.'/'.$quiz->id.'/questions/'.$q->id.'/move') ?>" style="display:inline;">
                                    <?= self::csrfField() ?>
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="btn btn-xs btn-default" title="<?= htmlspecialchars(__('Move up')) ?>">&uarr;</button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars($home.'/'.$quiz->id.'/questions/'.$q->id.'/move') ?>" style="display:inline;">
                                    <?= self::csrfField() ?>
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="btn btn-xs btn-default" title="<?= htmlspecialchars(__('Move down')) ?>">&darr;</button>
                                </form>
                                <a class="btn btn-xs btn-default" href="<?= htmlspecialchars($home.'/'.$quiz->id.'/questions/'.$q->id.'/edit') ?>"><?= htmlspecialchars(__('Edit')) ?></a>
                                <form method="post" action="<?= htmlspecialchars($home.'/'.$quiz->id.'/questions/'.$q->id.'/delete') ?>" style="display:inline;" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Delete this question?')), ENT_QUOTES) ?>);">
                                    <?= self::csrfField() ?>
                                    <button type="submit" class="btn btn-xs btn-danger"><?= htmlspecialchars(__('Delete')) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
        <?php
        $this->renderEditorFooter(array('editor_instructions'));
    }

    private function renderQuestionForm(Quiz $quiz, Question $question, $action, $is_new) {
        global $OUTPUT;
        $home = $this->toolHome(self::ROUTE);
        $type = $question->type;
        $labels = QuestionTypes::labels();
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $tf_true = false;
        foreach ( $question->answers as $ans ) {
            if ( strtolower(trim(strip_tags($ans->text))) === 'true' && $ans->correct ) {
                $tf_true = true;
            }
        }
        if ( $is_new && $type === QuestionTypes::TRUE_FALSE && count($question->answers) ) {
            $tf_true = $question->answers[0]->correct;
        }
        ?>
        <main class="container" role="main" id="main-content">
            <p><a href="<?= htmlspecialchars($home.'/'.$quiz->id.'/edit') ?>">&larr; <?= htmlspecialchars($quiz->title) ?></a></p>
            <h1><?= htmlspecialchars($is_new ? __('Add Question') : __('Edit Question')) ?></h1>
            <form method="post" action="<?= htmlspecialchars($action) ?>" id="question_form">
                <?= self::csrfField() ?>
                <input type="hidden" name="qtype" value="<?= htmlspecialchars($type) ?>">
                <div class="form-group">
                    <label><?= htmlspecialchars(__('Type')) ?></label>
                    <p class="form-control-static"><?= htmlspecialchars($labels[$type] ?? $type) ?></p>
                </div>
                <div class="form-group">
                    <label for="qtitle"><?= htmlspecialchars(__('Title (optional)')) ?></label>
                    <input type="text" class="form-control" id="qtitle" name="title" value="<?= htmlspecialchars($question->title) ?>">
                </div>
                <div class="form-group">
                    <label for="editor_prompt"><?= htmlspecialchars(__('Prompt')) ?></label>
                    <div class="ckeditor-container">
                        <textarea name="prompt" id="editor_prompt"><?= htmlspecialchars($question->prompt) ?></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label for="points"><?= htmlspecialchars(__('Points')) ?></label>
                    <input type="number" class="form-control" id="points" name="points" min="1" max="99" value="<?= (int) $question->points ?>" style="max-width:8em;">
                </div>
                <div class="form-group">
                    <label for="editor_feedback"><?= htmlspecialchars(__('General feedback (optional)')) ?></label>
                    <div class="ckeditor-container">
                        <textarea name="feedback" id="editor_feedback"><?= htmlspecialchars($question->feedback) ?></textarea>
                    </div>
                </div>

                <?php if ( $type === QuestionTypes::TRUE_FALSE ): ?>
                    <div class="form-group">
                        <label><?= htmlspecialchars(__('Correct answer')) ?></label>
                        <div>
                            <label class="radio-inline"><input type="radio" name="tf_correct" value="true" <?= $tf_true ? 'checked' : '' ?>> <?= htmlspecialchars(__('True')) ?></label>
                            <label class="radio-inline"><input type="radio" name="tf_correct" value="false" <?= ! $tf_true ? 'checked' : '' ?>> <?= htmlspecialchars(__('False')) ?></label>
                        </div>
                    </div>
                <?php elseif ( $type === QuestionTypes::ESSAY ): ?>
                    <div class="form-group">
                        <label for="editor_sample"><?= htmlspecialchars(__('Sample solution (optional, for graders)')) ?></label>
                        <div class="ckeditor-container">
                            <textarea name="sample_solution" id="editor_sample"><?= htmlspecialchars($question->sample_solution) ?></textarea>
                        </div>
                    </div>
                <?php elseif ( QuestionTypes::usesChoiceAnswers($type) ): ?>
                    <h3><?= htmlspecialchars(__('Choices')) ?></h3>
                    <p class="help-block"><?= $type === QuestionTypes::MULTIPLE_RESPONSE
                        ? htmlspecialchars(__('Mark every correct choice. At least two choices and one correct answer are required.'))
                        : htmlspecialchars(__('Mark the one correct choice. At least two choices are required.')) ?></p>
                    <div id="answer-rows">
                        <?php
                        $answers = $question->answers;
                        if ( count($answers) < 2 ) {
                            $answers[] = Answer::make('', false, count($answers) + 1);
                            $answers[] = Answer::make('', false, count($answers) + 1);
                        }
                        foreach ( $answers as $i => $ans ):
                        ?>
                            <?= $this->answerRowHtml($type, $i, $ans) ?>
                        <?php endforeach; ?>
                    </div>
                    <p><button type="button" class="btn btn-default btn-sm" id="add-answer"><?= htmlspecialchars(__('Add choice')) ?></button></p>
                <?php elseif ( QuestionTypes::usesAcceptedStrings($type) ): ?>
                    <h3><?= $type === QuestionTypes::FILL_BLANK
                        ? htmlspecialchars(__('Accepted answers'))
                        : htmlspecialchars(__('Matching patterns')) ?></h3>
                    <p class="help-block"><?= $type === QuestionTypes::FILL_BLANK
                        ? htmlspecialchars(__('Students must match one of these answers exactly. Matching is not case-sensitive.'))
                        : htmlspecialchars(__('A student answer is correct if it contains one of these strings (Common Cartridge “contains” matching, not a regular expression).')) ?></p>
                    <?php if ( $type === QuestionTypes::PATTERN_MATCH ): ?>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="case_sensitive" value="1" <?= $question->case_sensitive ? 'checked' : '' ?>>
                                <?= htmlspecialchars(__('Case sensitive')) ?>
                            </label>
                        </div>
                    <?php endif; ?>
                    <div id="answer-rows">
                        <?php
                        $answers = $question->answers;
                        if ( count($answers) < 1 ) {
                            $answers[] = Answer::make('', true, 1);
                        }
                        foreach ( $answers as $i => $ans ):
                        ?>
                            <?= $this->answerRowHtml($type, $i, $ans) ?>
                        <?php endforeach; ?>
                    </div>
                    <p><button type="button" class="btn btn-default btn-sm" id="add-answer"><?= htmlspecialchars($type === QuestionTypes::FILL_BLANK ? __('Add accepted answer') : __('Add pattern')) ?></button></p>
                <?php endif; ?>

                <p>
                    <button type="submit" class="btn btn-primary"><?= htmlspecialchars($is_new ? __('Add question') : __('Save question')) ?></button>
                    <a class="btn btn-default" href="<?= htmlspecialchars($home.'/'.$quiz->id.'/edit') ?>"><?= htmlspecialchars(__('Cancel')) ?></a>
                </p>
            </form>
        </main>
        <script type="text/html" id="answer-row-template"><?= $this->answerRowHtml($type, '__I__', Answer::make('', $type !== QuestionTypes::MULTIPLE_CHOICE && $type !== QuestionTypes::MULTIPLE_RESPONSE, 0)) ?></script>
        <?php
        $editors = array('editor_prompt', 'editor_feedback');
        if ( $type === QuestionTypes::ESSAY ) {
            $editors[] = 'editor_sample';
        }
        $this->renderEditorFooter($editors, true, $type);
    }

    private function answerRowHtml($type, $i, Answer $ans) {
        $is_choice = QuestionTypes::usesChoiceAnswers($type) && $type !== QuestionTypes::TRUE_FALSE;
        $idx = htmlspecialchars((string) $i);
        ob_start();
        ?>
        <div class="form-group answer-row" style="display:flex;gap:0.5em;align-items:flex-start;">
            <?php if ( $type === QuestionTypes::MULTIPLE_CHOICE ): ?>
                <label class="radio-inline" style="margin-top:8px;">
                    <input type="radio" name="mc_correct" value="<?= $idx ?>" <?= $ans->correct ? 'checked' : '' ?>>
                    <?= htmlspecialchars(__('Correct')) ?>
                </label>
            <?php elseif ( $type === QuestionTypes::MULTIPLE_RESPONSE ): ?>
                <label class="checkbox-inline" style="margin-top:8px;">
                    <input type="checkbox" name="answer_correct[<?= $idx ?>]" value="1" <?= $ans->correct ? 'checked' : '' ?>>
                    <?= htmlspecialchars(__('Correct')) ?>
                </label>
            <?php endif; ?>
            <input type="text" class="form-control" name="answer_text[<?= $idx ?>]" value="<?= htmlspecialchars($ans->text) ?>" placeholder="<?= htmlspecialchars($is_choice ? __('Choice text') : __('Answer')) ?>">
            <button type="button" class="btn btn-default remove-answer" title="<?= htmlspecialchars(__('Remove')) ?>">&times;</button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * @param string[] $ids
     */
    private function renderEditorFooter(array $ids, $with_answers = false, $type = '') {
        global $OUTPUT;
        $OUTPUT->footerStart();
        ?>
        <style>
        <?php \Tsugi\UI\CKEditor::renderStyles(['includeLinkPicker' => false, 'extraStyles' => '.ckeditor-container { min-height: 8em; }']); ?>
        </style>
        <?php \Tsugi\UI\CKEditor::renderScriptTag(); ?>
        <script type="text/javascript">
        var appHome = <?= json_encode(isset($GLOBALS['CFG']->apphome) ? rtrim($GLOBALS['CFG']->apphome, '/') : '') ?>;
        var pagesBase = appHome;
        var filesBase = appHome;
        <?php \Tsugi\UI\CKEditor::renderConfigScript(['toolbar' => array('heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'undo', 'redo')]); ?>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof ClassicEditor === 'undefined') return;
            <?= json_encode($ids) ?>.forEach(function(id) {
                var el = document.getElementById(id);
                if (!el) return;
                ClassicEditor.create(el, ClassicEditor.defaultConfig).catch(function(e) { console.error(e); });
            });
            <?php if ( $with_answers && $type !== QuestionTypes::TRUE_FALSE && $type !== QuestionTypes::ESSAY ): ?>
            var rows = document.getElementById('answer-rows');
            var addBtn = document.getElementById('add-answer');
            var tpl = document.getElementById('answer-row-template');
            function nextIndex() {
                return rows.querySelectorAll('.answer-row').length;
            }
            function reindex() {
                var list = rows.querySelectorAll('.answer-row');
                list.forEach(function(row, i) {
                    row.querySelectorAll('input').forEach(function(inp) {
                        if (inp.name === 'mc_correct') { inp.value = String(i); return; }
                        if (inp.name && inp.name.indexOf('answer_correct') === 0) inp.name = 'answer_correct[' + i + ']';
                        if (inp.name && inp.name.indexOf('answer_text') === 0) inp.name = 'answer_text[' + i + ']';
                    });
                });
            }
            if (addBtn && tpl && rows) {
                addBtn.addEventListener('click', function() {
                    var html = tpl.innerHTML.replace(/__I__/g, String(nextIndex()));
                    var wrap = document.createElement('div');
                    wrap.innerHTML = html.trim();
                    rows.appendChild(wrap.firstElementChild);
                    reindex();
                });
                rows.addEventListener('click', function(e) {
                    if (!e.target.classList.contains('remove-answer')) return;
                    var row = e.target.closest('.answer-row');
                    if (row) row.remove();
                    reindex();
                });
            }
            <?php endif; ?>
        });
        </script>
        <?php
        $OUTPUT->footerEnd();
    }
}
