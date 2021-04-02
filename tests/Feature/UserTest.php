<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;

    /** @test */
    public function storeNewUser()
    {
        $user = User::factory()->create();
        $this->assertDatabaseHas('users', ['nickname' => $user->nickname]);
    }

    /** @test */
    public function retrieveUser()
    {
        $user = User::factory()->create();
        $get_user = User::where('nickname', $user->nickname)
            ->where('password', $user->password)
            ->first();
        
        $user->refresh();

        $this->assertEquals($user->toArray(), $get_user->toArray());
    }

    /** @test */
    public function shouldNotRepeatNicknames()
    {
        $this->expectException(QueryException::class);

        $nickname = "D4rkS0rc3r3r";
        User::factory(['nickname' => $nickname])->count(2)->create();

        $this->assertDatabaseCount('users', 1);
    }

    /** @test */
    public function userRegister()
    {
        $nickname = "DarkFlameMaster";
        $password = "password";

        $response = $this->post('/api/users', [
            'nickname' => $nickname,
            'password' => $password
        ]);

        $response->assertCreated();
        $response->assertJsonFragment(['nickname' => $nickname]);
    }

    /** @test */
    public function shouldNotReturnPassword()
    {
        $nickname = "DarkFlameMaster";
        $password = "password";

        $response = $this->post('/api/users', [
            'nickname' => $nickname,
            'password' => $password
        ]);

        $response->assertJsonMissing(['password' => Hash::make($password)]);
    }

    /** @test */
    public function shouldReturnErrorWhenNicknameAlredyExists()
    {
        $nickname = "D4rkS0rc3r3r";
        User::factory(['nickname' => $nickname])->create();

        $response = $this->post('/api/users', [
            'nickname' => $nickname,
            'password' => 'password'
        ]);
        
        $response->assertStatus(409);
        $this->assertEquals("Nickname alredy exists", $response->getContent());
    }

    /** @test */
    public function usersRanking()
    {
        User::factory()->count(9)->create();
        User::factory()->count(2)->state(new Sequence(
            ['score' => 0],
            ['score' => 1000]
        ))->create();

        $user = User::factory([
            'nickname' => 'TestMaster',
            'password' => Hash::make('testword')
        ])->create();
        $response = $this->get('/api/ranking', [
            'nickname' => $user->nickname,
            'password' => 'testword'
        ]);
        $json = $response->decodeResponseJson();

        $response->assertSuccessful();
        
        $this->assertEquals($json[0]['score'], 1000);
        $this->assertEquals($json[11]['score'], 0);
    }

    /** @test */
    public function userInfo()
    {
        $nickname = "niconome";
        $password = "passapalavra";

        $user = User::factory([
            'nickname' => $nickname,
            'password' => Hash::make($password)
        ])->create();

        $response = $this->get("/api/users/$user->id", ['nickname' => $nickname, 'password' => $password]);
        
        $response->assertOk();
        $response->assertExactJson($user->toArray());
    }
}
