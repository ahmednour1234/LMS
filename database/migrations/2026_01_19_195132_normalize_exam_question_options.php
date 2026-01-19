<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalize existing data before schema change
        $questions = DB::table('exam_questions')->where('type', 'mcq')->whereNotNull('options')->get();

        foreach ($questions as $question) {
            $options = json_decode($question->options, true);
            $correctAnswer = $question->correct_answer;
            $normalizedOptions = null;
            $normalizedCorrectAnswer = null;

            if (is_array($options) && !empty($options)) {
                $normalizedOptions = $this->normalizeOptions($options);

                if ($normalizedOptions && $correctAnswer !== null) {
                    $normalizedCorrectAnswer = $this->normalizeCorrectAnswer($options, $correctAnswer, $normalizedOptions);
                }
            }

            // Update the record
            DB::table('exam_questions')
                ->where('id', $question->id)
                ->update([
                    'options' => $normalizedOptions ? json_encode($normalizedOptions) : null,
                    'correct_answer' => $normalizedCorrectAnswer,
                ]);
        }

        // Change column type from string to integer
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->integer('correct_answer')->nullable()->change();
        });
    }

    /**
     * Normalize options to canonical format [{"ar":"...","en":"..."}]
     */
    private function normalizeOptions(array $options): ?array
    {
        // Format 2: {"ar":["...","..."],"en":["...","..."]}
        if (isset($options['ar']) && isset($options['en']) && is_array($options['ar']) && is_array($options['en'])) {
            $normalized = [];
            $maxLen = max(count($options['ar']), count($options['en']));
            for ($i = 0; $i < $maxLen; $i++) {
                $normalized[] = [
                    'ar' => $options['ar'][$i] ?? '',
                    'en' => $options['en'][$i] ?? '',
                ];
            }
            return $normalized;
        }

        // Format 1 or 3: Array of objects
        if (isset($options[0]) && is_array($options[0])) {
            $normalized = [];
            foreach ($options as $option) {
                if (!is_array($option)) {
                    continue;
                }

                $ar = '';
                $en = '';

                // Format 1: {"option_lang":"en","option_en":"good","option_ar":"..."}
                if (isset($option['option_en'])) {
                    $en = $option['option_en'];
                }
                if (isset($option['option_ar'])) {
                    $ar = $option['option_ar'];
                }
                // Also check for "option" field (might be used for ar or en)
                if (isset($option['option']) && is_string($option['option'])) {
                    if (empty($en) && (!isset($option['option_en']) || empty($option['option_en']))) {
                        $en = $option['option'];
                    }
                    if (empty($ar) && (!isset($option['option_ar']) || empty($option['option_ar']))) {
                        $ar = $option['option'];
                    }
                }

                // Format 3: {"option":"ar text","option_en":"en text","is_correct":true}
                // Handled above, but is_correct is handled in normalizeCorrectAnswer

                $normalized[] = [
                    'ar' => $ar ?: '',
                    'en' => $en ?: '',
                ];
            }
            return !empty($normalized) ? $normalized : null;
        }

        return null;
    }

    /**
     * Normalize correct_answer to integer index
     */
    private function normalizeCorrectAnswer(array $oldOptions, $oldCorrectAnswer, array $normalizedOptions): ?int
    {
        // If old correct_answer is already an integer, validate it
        if (is_numeric($oldCorrectAnswer)) {
            $index = (int) $oldCorrectAnswer;
            if ($index >= 0 && $index < count($normalizedOptions)) {
                return $index;
            }
        }

        // Try to find by is_correct flag (Format 3)
        foreach ($oldOptions as $index => $option) {
            if (is_array($option) && isset($option['is_correct']) && $option['is_correct'] === true) {
                if ($index < count($normalizedOptions)) {
                    return $index;
                }
            }
        }

        // Try to match by string value
        if (is_string($oldCorrectAnswer)) {
            foreach ($normalizedOptions as $index => $normalized) {
                if ($normalized['ar'] === $oldCorrectAnswer || $normalized['en'] === $oldCorrectAnswer) {
                    return $index;
                }
            }
        }

        // Invalid - return null
        return null;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->string('correct_answer')->nullable()->change();
        });
    }
};
