<?php
namespace App\Core;

/** Base controller: renders views inside a layout and returns JSON. */
abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        $data['errors'] ??= $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);
        extract($data);

        ob_start();
        require ROOT . "/app/Views/$view.php";
        $content = ob_get_clean();

        require ROOT . "/app/Views/layouts/$layout.php";
        unset($_SESSION['_old']);
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }

    /** Go back to the form with errors and the values the user typed. */
    protected function backWithErrors(string $to, array $errors, array $input = []): void
    {
        unset($input['password'], $input['password_confirm'], $input['_csrf'], $input['card_number'], $input['cvv']);
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old'] = $input;
        flash('error', 'Please fix the highlighted fields.');
        redirect($to);
    }
}
