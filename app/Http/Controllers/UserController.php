<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Listar os usuarios
    public function index(){
    
        //Recuperar os registros do banco de dados
        $users = User::get();

        // Carregar a VIEW
        return view('users.index', ['users' => $users]);

    }

    // Importar os dados do Excel
    public function import(Request $request){

        // Validar o arquivo
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ],[
            'file.required' => 'O campo arquivo é obrigatorio',
            'file.mimes' => 'Arquivo invalido, necessario enviar arquivo CSV.',
            'file.max' => 'Tamanho do arquivo exede :max Mb',
        ]);

        // Criar o array com as colunas no banco de dados
        $headers = ['name', 'email', 'password'];

        //Receber o arquivo, ler dados e converter a string em array
        $dataFile = array_map('str_getcsv', file($request->file('file')));

        // Criar a variável para receber a quantidade de registros cadastrados
        $numberRegisteredRecords = 0;

        // Criar a variavel que recebe os e-mail que estão cadastrados no banco de dados 
        $emailAlreadyRegistered = false;

        //Percorrer as linhas do arquivo CSV
        foreach($dataFile as $keyData => $row){

            // Converter a linha em array
            $values = explode(';', $row[0]);

            // Percorrer as colunas do cabeçalho
            foreach ($headers as $key => $header){

                // Atribuir o valor ao elemento do array
                $arrayValues[$keyData][$header] = $values[$key];

                // Verificar se a coluna é e-mail
                if($header == "email"){
                    // Verificar se o e-mail já está cadastrado no banco de dados
                    
                    if(User::where('email', $arrayValues[$keyData]['email'])->first()){
                        
                        // Atribuir o valor ao elemento do array
                        $emailAlreadyRegistered .= $arrayValues[$keyData]['email'] . ", ";
                    }
                }

            // Verificar se a coluna é senha
            if($header == "password"){

                // Criptografar a senha
                // $arrayValues[$keyData][$header] = Hash::make($arrayValues[$keyData]['password'], ['rounds' => 12]);     
            
                //Atribuir a senha ao elemento array, Gerar uma senha aleatória com 7 caracteres
                $arrayValues[$keyData][$header] = Hash::make(Str::random(7), ['rounds' => 12]);     
            }

            }
            // Incrementar mais um registro na quantidade de registros que serão cadastrados
            $numberRegisteredRecords++;
        }
        // Verificar se existe e-mail já cadastrado, retorna erro e não cadastra no banco de dados
        if($emailAlreadyRegistered){
            // Redirecionar o usuário para a página anterior e enviar a mensagem de erro
            return back()->with('error', 'Dados não importados. Existem e-mails já cadastrados:<br> ' . $emailAlreadyRegistered);
        }

        // Cadastrar registros no banco de dados 
        user::insert($arrayValues);

        // Redirecionar o usuário para a página anterior e enviar a mensagem de sucesso
        return back()->with('success', 'Dados importados com sucesso. <br>Quantidade: ' . $numberRegisteredRecords);
    }
}
