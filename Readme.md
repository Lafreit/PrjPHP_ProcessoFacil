# PrjPHP_ProcessoFacil

   Repositorio do projeto: PrjPHP_ProcessoFacil.

   Link GitHub: https://github.com/Lafreit/PrjPHP_ProcessoFacil

   Nome do projeto: ProcessoFacil

   Disciplina: Desenvolvimento Web II - Fatec Araras

   Professor: Orlando Saraiva Júnior

   Discente: Luíz Antônio de Freitas

   Curso: Desenvolvimento de Software Multiplataforma - DSM

   Ano: 2025

# Objetivos deste projeto.

Primeiramente, este projeto visa a atender a exigência para conclusão da disciplina desenvolvimento web II.
Entretanto, pretende também ser um protótipo para o desenvolvimento futuro de um sistema de gestão de processos judiciais e gestão de clientes para auxiliar advogados e bancas de advogados na gestão diária de atos processuais e na gestão da relação com as partes e demais sujeitos do processo durante o trâmite de processos judiciais.

# Descrição dos requisitos funcionais.

O projeto ProcessoFacil, neste estágio de concepção, pretende ser um sistema de controle de processos judicias, desenvolvido em PHP, que permite ao usuário inserir, acessar, modificar e deletar a movimentação judicial e administrativa dos processos de clientes, desde a instância de primeiro grau, de segundo grau, especial e extraordinária.

# Descrição acadêmica do Projeto "ProcessoFacil"

O projeto ProcessoFácil é uma aplicação web desenvolvida em PHP com foco na gestão simplificada de processos jurídicos. Ele foi concebido para atender às necessidades de escritórios de advocacia ou profissionais do direito que buscam uma ferramenta eficiente para organizar informações de clientes, partes ex-adversas, advogados associados e os próprios processos.

# Arquitetura e Tecnologias Empregadas

A arquitetura do ProcessoFácil segue um padrão modular, com classes PHP dedicadas a diferentes entidades do domínio, como Processo, Cliente, Advogado, ParteExAdversa e Conexao. Essa abordagem promove a separação de responsabilidades e facilita a manutenção e escalabilidade do código.

   1. Linguagem de Programação: PHP.
   
   2. Banco de Dados: Utiliza MySQL (ou um SGBD compatível) para persistência dos dados. A interação com o banco é realizada através da extensão PDO (PHP Data Objects), que oferece uma interface leve e consistente para acesso a bancos de dados, além de suporte a prepared statements para prevenir ataques de SQL Injection.
   
   3. Orientação a Objetos: O projeto emprega conceitos de Programação Orientada a Objetos (POO), encapsulando a lógica de negócios em classes, como a classe Processo que gerencia as operações CRUD (Create, Read, Update, Delete) relacionadas aos processos e suas entidades associadas.
   
   4. Transações de Banco de Dados: Operações complexas que envolvem múltiplas inserções ou atualizações (como a criação ou atualização de um processo, que afeta clientes, partes ex-adversas e advogados) são realizadas dentro de transações ACID. Isso garante a atomicidade das operações: ou todas as etapas são concluídas com sucesso (commit), ou, em caso de falha, todas as alterações são desfeitas (rollback), mantendo a integridade referencial do banco de dados.
   
   5. Tratamento de Erros: O código incorpora blocos try-catch para lidar com exceções PDOException, registrando mensagens de erro via error_log para depuração e monitoramento.

# Funcionalidades Principais Implementadas

Até o momento, o projeto abrange as seguintes funcionalidades essenciais para a gestão de processos:

   1. Criação de Processos: Permite o registro de novos processos, incluindo informações detalhadas do cliente, da parte ex-adversa e a associação de múltiplos advogados. A lógica foi aprimorada para reutilizar registros de advogados existentes (verificando pela OAB) e criar novos apenas quando necessário, otimizando o armazenamento e evitando duplicação.
   2. Listagem de Processos: Apresenta uma visão geral de todos os processos cadastrados, exibindo informações cruciais como número do processo, data de protocolo, natureza da ação, e nomes do cliente e da parte ex-adversa.
   3. Visualização Detalhada do Processo: Permite consultar todos os dados de um processo específico, incluindo informações completas do cliente, da parte ex-adversa e dos advogados vinculados.
   4. Atualização de Processos: Oferece a capacidade de modificar os dados de um processo existente, incluindo as informações do cliente, da parte ex-adversa e a reassociação de advogados (removendo os antigos e adicionando/atualizando os novos).
   5. Exclusão de Processos: Implementa a funcionalidade de remover um processo. Graças à configuração de ON DELETE CASCADE no esquema do banco de dados, a exclusão de um processo principal automaticamente acarreta na exclusão de movimentações e associações de advogados relacionadas, garantindo a integridade dos dados. Adicionalmente, a lógica prevê a exclusão de registros de clientes e partes ex-adversas que não estejam mais associados a nenhum outro processo, otimizando o banco.

# Formas de Acesso ao Sistema

O ProcessoFacil, como uma aplicação web, é acessado via navegador através de um servidor web (como Apache) que hospeda os arquivos PHP. As principais formas de interação e acesso às funcionalidades são:

   1. Via URL Direta: Cada funcionalidade ou página do sistema é acessada através de URLs específicas. Por exemplo:
      
      1.1. Página Inicial/Listagem de Processos: http://localhost/ProcessoFacil/public/listar_processos.php (ou o caminho configurado para o diretório public).
      
      1.2. Formulário de Criação de Novo Processo: http://localhost/ProcessoFacil/public/criar_processo.php.
      
      1.3. Visualização de Processo Específico: http://localhost/ProcessoFacil/public/detalhes_processo.php?id=<ID_DO_PROCESSO>.
      
      1.4. Formulário de Edição de Processo: http://localhost/ProcessoFacil/public/editar_processo.php?id=<ID_DO_PROCESSO>.
      
      1.5. Execução de Exclusão: http://localhost/ProcessoFacil/public/deletar_processo.php?id=<ID_DO_PROCESSO>.
   
   2. Interação por Formulários HTML: A criação e atualização de processos ocorrem através de formulários HTML, onde o usuário insere os dados, que são então enviados via método HTTP POST para os scripts PHP responsáveis pelo processamento e gravação no banco de dados.
   
   3. Links de Navegação: Dentro das páginas do sistema (por exemplo, na listagem de processos), links (<a> tags) são utilizados para navegar entre as visualizações, como ir para a página de detalhes de um processo ou para o formulário de edição.

O projeto ProcessoFacil, no estágio atual, constitui-se em uma perspectiva de uma base sólida para uma aplicação de gestão, com atenção à segurança (PDO e transações) e organização do código, sendo um ponto de partida para futuras expansões, aprimoramentos e agregação de novas tecnologias.