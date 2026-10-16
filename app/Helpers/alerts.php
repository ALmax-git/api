<?php

use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

/**
 * Show a success alert message.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('alert_success')) {
    function alert_success($text = null): void
    {
        LivewireAlert::title('🥳 Success!')
            ->text($text ?? _app('success_msg'))
            ->toast()
            ->position('top')
            ->success()
            ->show();
    }
}

/**
 * Show an info alert message.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('alert_info')) {
    function alert_info($text = null): void
    {
        LivewireAlert::title('😉 Hay')
            ->text($text)
            // ->toast()
            ->position('center')
            ->info()
            ->cancelButtonText('Close')
            ->withConfirmButton() // Enables button with default text

            ->show();
    }
}

/**
 * Show a question alert message.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('alert_question')) {
    function alert_question($text = null): void
    {
        LivewireAlert::title('🤔')
            ->text($text . '?')
            // ->toast()
            ->timer(null)
            ->position('center')
            ->cancelButtonText('Close')
            ->withConfirmButton() // Enables button with default text
            ->question()
            ->show();
    }
}

/**
 * Show a warning alert message.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('alert_warning')) {
    function alert_warning($text = null): void
    {
        LivewireAlert::title('🤕')
            ->text($text)
            // ->toast()
            ->timer(null)
            ->position('center')
            ->cancelButtonText('Close')
            ->withConfirmButton() // Enables button with default text
            ->warning()
            ->show();
    }
}

/**
 * Show an error alert message.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('alert_error')) {
    function alert_error($text = null): void
    {
        LivewireAlert::title('😳 Whoopsi')
            ->text($text)
            ->timer(null)
            ->position('center')
            ->cancelButtonText('Close')
            ->withConfirmButton() // Enables button with default text
            ->error()
            ->show();
    }
}

/**
 * Show an info alert when a record is not found.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('record_not_founnd')) {
    function record_info($text = null): void
    {
        LivewireAlert::title('🤔')
            ->text($text ?? _app('record_not_founnd'))
            ->toast()
            ->position('center')
            ->info()
            ->show();
    }
}

/**
 * Show a success alert when a record is saved.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('record_saved')) {
    function record_saved($text = null): void
    {
        LivewireAlert::title('🤓')
            ->text($text ?? _app('record_saved'))
            ->toast()
            ->position('center')
            ->success()
            ->show();
    }
}

/**
 * Show an error alert when failing to save a record.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('fail_to_save')) {
    function fail_to_save($text = null): void
    {
        LivewireAlert::title('😳')
            ->text($text ?? _app('fail_to_save'))
            ->toast()
            ->position('center')
            ->error()
            ->show();
    }
}

/**
 * Show an error alert when failing to delete a record.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('fail_to_delete')) {
    function fail_to_delete($text = null): void
    {
        LivewireAlert::title('😞')
            ->text($text ?? _app('fail_to_delete'))
            ->toast()
            ->position('center')
            ->error()
            ->show();
    }
}

/**
 * Show a success alert when a record is deleted.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('record_deleted')) {
    function record_deleted($text = null): void
    {
        LivewireAlert::title('🙂')
            ->text($text ?? _app('record_deleted'))
            ->toast()
            ->position('center')
            ->success()
            ->show();
    }
}

/**
 * Show a success alert when a record is archived.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('record_archieved')) {
    function record_archieved($text = null): void
    {
        LivewireAlert::title('🗑️')
            ->text($text ?? _app('record_archieved'))
            ->toast()
            ->position('center')
            ->success()
            ->show();
    }
}

/**
 * Show an error alert for incorrect password.
 *
 * @param string|null $text Custom message text (optional)
 * @return void
 */
if (!function_exists('alert_password_error')) {
    function alert_password_error($text = null): void
    {
        LivewireAlert::title('😶‍🌫️')
            ->text($text ?? 'Incorrect password. Please try again')
            ->toast()
            ->position('center')
            ->error()
            ->show();
    }
}
