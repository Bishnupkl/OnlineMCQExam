<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionsFromSqlSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [1, 'A train 110m length travel at 60 km/hr. The time in which a man walking at 6 km/hr in opposite direction pass the train is', '6.6 sec', '66 sec', '6 sec', '5.4 sec', '6 sec', '1'],
            [2, 'The maximum velocity of a car in level road of radius 80m in which car move without skidding if coefficient of friction is 0.25 is', '10 m/s', '8 m/s', '12 m/s', '14 m/s', '12 m/s', '1'],
            [3, 'A fly wheel of mass 25 kg has a radius of 0.2m.It is making 240 rpm. The torque required to stop in rest in 20 sec is', '314 Nm', '0.628 Nm', '0.314 Nm', '1.26 Nm', '0.628 Nm', '1'],
            [4, 'A cube is subjected to a pressure of 5*105 N/m2. Each side of the cube is shortened by 1% the bulk modulus of cube', '8.3*106 N/m2', '1.67*107 N/m2', '3.32*107 N/m2', '6.4*107 N/m2', '1.67*107 N/m2', '1'],
            [5, 'A spherical liquid drop of radius R is divided into 8 equal droplets. It surface tension is T then work done in the process will be', '2?R2T', '3?R2T', '4?R2T', '5?R2T', '4?R2T', '1'],
            [6, 'The number of electrons present in 3.2 grams of CH4 is', '0.25 NA', '0.5 NA', '0.75 NA', '1 NA', '1 NA', '1'],
            [7, 'The vapour density of a metal chloride is 99 and the equivalent weight of metal is 63.5 . The formula of metal chloride is', 'MCl', 'M2Cl2', 'MCl2', 'M2Cl4', 'M2Cl2', '1'],
            [8, 'The mole fraction of NaOH in 0.4M NaOH solution is', '7.5*10-3', '3.25*103', '7.15*10-4', '3.25*10-4', '7.5*10-3', '1'],
            [9, 'How many unpaired electrons are present in Fe3+ ion', '3', '5', '1', '6', '5', '1'],
            [10, 'Acetaldehyde and benzaldehyde can be distinguished by', 'Tollen\'s test', '2,4-DNP test', 'Iodoform test', 'Wolff-Kishner', 'Iodoform test', '1'],
            [11, 'Inscription on a tomb is called……………..', 'Epitaph', 'cemetery', 'Morgue', 'demagogue', 'Epitaph', '1'],
            [12, 'After he finished the exam, he ………… to the teacher.', 'Handed over it', 'handed it over', 'handed it out', 'handed out it', 'handed it over', '1'],
            [13, 'Every week she …………. her sister in her village to talk to her.', 'Calls over', 'calls on', 'calls out', 'calls up', 'calls out', '1'],
            [14, 'He has a coat ……………… five pockets.', 'of', 'about ', 'with ', 'in ', 'with', '1'],
            [15, 'To hit below the belt means:', 'to use force', 'to use unfair means', 'to use energy', 'to use leather', 'to use unfair means', '1'],
            [16, 'For function f(x, y) = sin-1(x2 + y2) critical points are found. Now a new graph g(x, y) is formed by coupling graphs f(x, y) and f(x, y) = - sin-1(x2 + y2). What are the critical points of g(x, y)', '(0,0)', '(0,-90)', '(90,0)', 'none', '(0,-90)', '1'],
            [17, 'If the Hessian matrix of a function is zero then the critical point is', 'It cannot be concluded', 'Always at Origin', 'Depends on Function', '(100,100)', 'It cannot be concluded', '1'],
            [18, "What is the maximum value of the function f(x, y) = 3xy + 4x2y2 in the region\r\nx=0; y=0; 2x + y = 2\r\n", '1', '0', '100', '10', '10', '1'],
            [19, 'Divide 120 into three parts so that the sum of their products taken two at a time is maximum. If x, y, z are two parts, find value of x, y and z', 'x=40, y=40, z=40', 'x=38, y=50, z=32', 'x=50, y=40, z=30', 'x=80, y=30, z=50', 'x=38, y=50, z=32', '1'],
            [20, 'For a Poisson Distribution, if mean(m) = 1, then P(1) is', '1/e', 'e', 'e/2', 'Indeterminate', '1/e', '1'],
        ];

        foreach ($questions as [$id, $question, $choice1, $choice2, $choice3, $choice4, $correctAnswer, $mark]) {
            DB::table('question_table')->updateOrInsert(
                ['q_id' => $id],
                [
                    'question' => $question,
                    'choice1' => $choice1,
                    'choice2' => $choice2,
                    'choice3' => $choice3,
                    'choice4' => $choice4,
                    'correct_ans' => $correctAnswer,
                    'mark' => $mark,
                    'uploaded_by' => 'oee.sql',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
