<?php
/**
 * Take a Quiz1 quiz (students and instructors).
 *
 * Included from Quiz1. Fully-qualified names so this file does not depend
 * on the caller's namespace.
 *
 * Expected: $quiz, $home, $lessons_url, $can_edit, $result (null or grade array)
 */
$questions = $quiz->orderedQuestions();
$submitted = $result !== null;
$action = $home.'/'.$quiz->id;
?>
<style>
.quiz1-take .quiz1-q { margin: 1.5em 0; padding: 1em; border: 1px solid #ddd; border-radius: 4px; }
.quiz1-take .quiz1-q.quiz1-correct { border-color: #28a745; background: #f4fff6; }
.quiz1-take .quiz1-q.quiz1-incorrect { border-color: #dc3545; background: #fff6f6; }
.quiz1-take .quiz1-q.quiz1-unscored { border-color: #ffc107; background: #fffdf2; }
.quiz1-take .quiz1-prompt { margin-bottom: 0.75em; }
.quiz1-take .quiz1-choice { display: block; margin: 0.35em 0; font-weight: normal; }
.quiz1-take .quiz1-score { font-size: 1.1em; margin: 1em 0; }
.quiz1-take .quiz1-fb { margin-top: 0.75em; color: #555; }
</style>
<main class="container quiz1-take" role="main" id="main-content">
    <p>
        <?php if ( $lessons_url ) { ?>
            <a href="<?= htmlspecialchars($lessons_url) ?>">&larr; <?= htmlspecialchars(__('Back to Lessons')) ?></a>
        <?php } else if ( $can_edit ) { ?>
            <a href="<?= htmlspecialchars($home) ?>">&larr; <?= htmlspecialchars(__('All quizzes')) ?></a>
        <?php } ?>
        <?php if ( $can_edit ) { ?>
            <span class="pull-right">
                <a class="btn btn-default btn-sm" href="<?= htmlspecialchars($home.'/'.$quiz->id.'/edit') ?>"><?= htmlspecialchars(__('Edit quiz')) ?></a>
            </span>
        <?php } ?>
    </p>
    <h1><?= htmlspecialchars($quiz->title) ?></h1>
    <?php if ( ! \Tsugi\Services\Quiz1\Question::isBlankHtml($quiz->instructions) ) { ?>
        <div class="quiz1-instructions"><?= \Tsugi\Services\Quiz1\Html::purify($quiz->instructions) ?></div>
    <?php } ?>

    <?php if ( $submitted ) { ?>
        <p class="quiz1-score alert alert-info">
            <?= htmlspecialchars(__('Auto-scored:')) ?>
            <strong><?= (int) $result['earned'] ?></strong>
            /
            <?= (int) $result['possible'] ?>
            <?php if ( (int) $result['essay_possible'] > 0 ) { ?>
                <?= htmlspecialchars(sprintf(__('(%s points of essay not auto-scored)'), (int) $result['essay_possible'])) ?>
            <?php } ?>
        </p>
    <?php } ?>

    <?php if ( count($questions) < 1 ) { ?>
        <p><?= htmlspecialchars(__('This quiz has no questions yet.')) ?></p>
    <?php } else { ?>
        <form method="post" action="<?= htmlspecialchars($action) ?>">
            <?= \Tsugi\Controllers\Quiz1::csrfField() ?>
            <?php foreach ( $questions as $q ) {
                $qid = (int) $q->id;
                $item = ( $submitted && isset($result['items'][$qid]) ) ? $result['items'][$qid] : null;
                $status = is_array($item) ? ($item['status'] ?? '') : '';
                $klass = $status !== '' ? ' quiz1-'.$status : '';
                $got = is_array($item) ? $item['submitted'] : '';
                ?>
                <div class="quiz1-q<?= $klass ?>">
                    <p><strong><?= (int) $q->sequence ?>.</strong>
                        <?= htmlspecialchars(\Tsugi\Services\Quiz1\QuestionTypes::label($q->type)) ?>
                        (<?= (int) $q->points ?> <?= htmlspecialchars(__('points')) ?>)
                        <?php if ( $submitted && is_array($item) ) { ?>
                            <?php if ( $status === \Tsugi\Services\Quiz1\Grader::CORRECT ) { ?>
                                <span class="label label-success"><?= htmlspecialchars(__('Correct')) ?></span>
                            <?php } else if ( $status === \Tsugi\Services\Quiz1\Grader::INCORRECT ) { ?>
                                <span class="label label-danger"><?= htmlspecialchars(__('Incorrect')) ?></span>
                            <?php } else { ?>
                                <span class="label label-warning"><?= htmlspecialchars(__('Not auto-scored')) ?></span>
                            <?php } ?>
                        <?php } ?>
                    </p>
                    <?php if ( $q->title !== '' ) { ?>
                        <p><?= htmlspecialchars($q->title) ?></p>
                    <?php } ?>
                    <div class="quiz1-prompt"><?= \Tsugi\Services\Quiz1\Html::purify($q->prompt) ?></div>

                    <?php if ( \Tsugi\Services\Quiz1\QuestionTypes::usesChoiceAnswers($q->type) ) {
                        $multi = $q->type === \Tsugi\Services\Quiz1\QuestionTypes::MULTIPLE_RESPONSE;
                        $picked = $multi
                            ? (is_array($got) ? array_map('intval', $got) : array())
                            : array((int) $got);
                        foreach ( $q->nonEmptyAnswers() as $ans ) {
                            $aid = (int) $ans->id;
                            $checked = in_array($aid, $picked, true);
                            $name = $multi ? 'q'.$qid.'[]' : 'q'.$qid;
                            $input_type = $multi ? 'checkbox' : 'radio';
                            ?>
                            <label class="quiz1-choice">
                                <input type="<?= $input_type ?>" name="<?= htmlspecialchars($name) ?>" value="<?= $aid ?>"
                                    <?= $checked ? 'checked' : '' ?>
                                    <?= $submitted ? 'disabled' : '' ?>>
                                <?= \Tsugi\Services\Quiz1\Html::purify($ans->text) ?>
                                <?php if ( $submitted && $ans->correct ) { ?>
                                    <em>(<?= htmlspecialchars(__('correct')) ?>)</em>
                                <?php } ?>
                            </label>
                            <?php
                        }
                    } else if ( $q->type === \Tsugi\Services\Quiz1\QuestionTypes::ESSAY ) { ?>
                        <textarea class="form-control" name="q<?= $qid ?>" rows="6" <?= $submitted ? 'readonly' : '' ?>><?= htmlspecialchars(is_string($got) ? $got : '') ?></textarea>
                    <?php } else { ?>
                        <input type="text" class="form-control" name="q<?= $qid ?>"
                               value="<?= htmlspecialchars(is_string($got) ? $got : '') ?>"
                               <?= $submitted ? 'readonly' : '' ?>>
                    <?php } ?>

                    <?php if ( $submitted && ! \Tsugi\Services\Quiz1\Question::isBlankHtml($q->feedback) ) { ?>
                        <div class="quiz1-fb"><?= \Tsugi\Services\Quiz1\Html::purify($q->feedback) ?></div>
                    <?php } ?>
                    <?php if ( $submitted && $q->type === \Tsugi\Services\Quiz1\QuestionTypes::ESSAY && ! \Tsugi\Services\Quiz1\Question::isBlankHtml($q->sample_solution) ) { ?>
                        <div class="quiz1-fb"><strong><?= htmlspecialchars(__('Sample solution')) ?>:</strong> <?= \Tsugi\Services\Quiz1\Html::purify($q->sample_solution) ?></div>
                    <?php } ?>
                </div>
            <?php } ?>

            <p>
                <?php if ( ! $submitted ) { ?>
                    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('Submit quiz')) ?></button>
                <?php } else { ?>
                    <a class="btn btn-default" href="<?= htmlspecialchars($action) ?>"><?= htmlspecialchars(__('Try again')) ?></a>
                <?php } ?>
            </p>
        </form>
    <?php } ?>
</main>
