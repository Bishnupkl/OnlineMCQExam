<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_account', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('stu_reg', function (Blueprint $table) {
            $table->id();
            $table->string('name', 1100);
            $table->string('address')->nullable();
            $table->string('fatname', 1100)->nullable();
            $table->date('dob')->nullable();
            $table->string('phone', 110)->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->date('reg_date');
            $table->string('gender', 20)->nullable();
            $table->string('exam_status')->default('not taken');
            $table->string('salting_value', 1100)->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_reg', function (Blueprint $table) {
            $table->id('t_id');
            $table->string('t_name', 50);
            $table->string('t_gender', 20)->nullable();
            $table->string('t_address', 50)->nullable();
            $table->string('t_phone', 100)->nullable();
            $table->string('t_email', 50)->unique();
            $table->string('t_password');
            $table->string('subject', 50)->nullable();
            $table->string('rdate', 100)->nullable();
            $table->string('permission', 200)->nullable();
            $table->string('salting_value', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('question_table', function (Blueprint $table) {
            $table->id('q_id');
            $table->text('question');
            $table->string('choice1');
            $table->string('choice2');
            $table->string('choice3');
            $table->string('choice4');
            $table->string('correct_ans');
            $table->decimal('mark', 8, 2)->default(1);
            $table->string('uploaded_by')->nullable();
            $table->timestamps();
        });

        Schema::create('notice', function (Blueprint $table) {
            $table->id('n_id');
            $table->timestamp('n_date')->useCurrent();
            $table->string('n_heading')->nullable();
            $table->string('n_text')->nullable();
            $table->text('n_description')->nullable();
            $table->timestamps();
        });

        Schema::create('exam_date', function (Blueprint $table) {
            $table->id();
            $table->date('edate');
            $table->timestamps();
        });

        Schema::create('rdate', function (Blueprint $table) {
            $table->id();
            $table->date('rdate');
            $table->timestamps();
        });

        Schema::create('result', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->unsignedInteger('ques_attempted')->default(0);
            $table->decimal('mark_obtained', 8, 2)->default(0);
            $table->unsignedInteger('right_answer')->default(0);
            $table->unsignedInteger('wrong_answer')->default(0);
            $table->string('status')->default('not taken');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result');
        Schema::dropIfExists('rdate');
        Schema::dropIfExists('exam_date');
        Schema::dropIfExists('notice');
        Schema::dropIfExists('question_table');
        Schema::dropIfExists('teacher_reg');
        Schema::dropIfExists('stu_reg');
        Schema::dropIfExists('admin_account');
    }
};
