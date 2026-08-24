<?php
declare(strict_types=1);

namespace Nexus\Validation;

use Nexus\Http\Request;

/**
 * Base Auto-Validating Form Request Object
 */
abstract class FormRequest extends Request
{
    abstract public function rules(): array;

    public function authorize(): bool
    {
        return true;
    }

    public static function createFromRequest(Request $request): static
    {
        return new static(
            $request->method,
            $request->uri,
            $request->headers,
            $request->query,
            $request->post,
            $request->files,
            $request->cookies,
            $request->rawBody
        );
    }

    public function validateResolved(): array
    {
        if (!$this->authorize()) {
            throw new \RuntimeException('Unauthorized request action.', 403);
        }

        $inputData = array_merge($this->query, $this->post, $this->json() ?: []);
        $validator = Validator::make($inputData, $this->rules());
        return $validator->validate();
    }
}
