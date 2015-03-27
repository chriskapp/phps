PHPS
====

## About

PHPS is a tool that generates an index of classes found in PHP source files.
It searches in an folder for all class definitions and writes them into an 
sqlite database. Even PHP is a dynamically typed language it tries to detect the
return types and type-hints based on doc annotations. Then the tool can be used 
to query the index. It should help to provide more insight of an code base. This 
can be used i.e. by IDEs to provide more intelligent code completion.

PHPS uses the great nikic/php-parser to parse all definitions. While scanning it
uses an in-memory sqlite database to store all definitions. If the scan process 
was completed all definitions gets written chunk wise into an actual sqlite 
file. Because of this the indexing process should be fast even if you have a 
huge code base.

## Usage

To create an index goto the directory which you want index and type

    phps init

PHPS will now recursively traverse all folders and parse every .php file. A new
file phps.db will be created in the current working dir which contains the 
sqlite database with all definitions. You can use the -v switch to see which
files are scanned.

You can now use the index. I.e. to get all available classes in the 
Symfony\Component\Console\Command namesspace you can use the command:

    phps search Symfony\Component\Console\Command

which gives you an output like:

     Symfony\Component\Console\Command\Command
     Symfony\Component\Console\Command\HelpCommand
     Symfony\Component\Console\Command\ListCommand

To get all method definitions from the class 
Symfony\Component\Console\Command\Command you can use the command:

    phps desc Symfony\Component\Console\Command\Command

which produces an output like:

     - $application
     - $name
     - $processTitle
     - $aliases
     - $definition
     - $help
     - $description
     - $ignoreValidationErrors
     - $applicationDefinitionMerged
     - $applicationDefinitionMergedWithArgs
     - $code
     - $synopsis
     - $helperSet
     + __construct($name)
     + ignoreValidationErrors()
     + setApplication(Symfony\Component\Console\Application $application)
     + setHelperSet(Symfony\Component\Console\Helper\HelperSet $helperSet)
     + getHelperSet(): Symfony\Component\Console\Helper\HelperSet
     + getApplication(): Symfony\Component\Console\Application
     + isEnabled(): bool
     # configure()
     # execute(Symfony\Component\Console\Input\InputInterface $input, Symfony\Component\Console\Output\OutputInterface $output): null|int
     # interact(Symfony\Component\Console\Input\InputInterface $input, Symfony\Component\Console\Output\OutputInterface $output)
     # initialize(Symfony\Component\Console\Input\InputInterface $input, Symfony\Component\Console\Output\OutputInterface $output)
     + run(Symfony\Component\Console\Input\InputInterface $input, Symfony\Component\Console\Output\OutputInterface $output): int
     + setCode($code): Symfony\Component\Console\Command\Command
     + mergeApplicationDefinition($mergeArgs)
     + setDefinition($definition): Symfony\Component\Console\Command\Command
     + getDefinition(): Symfony\Component\Console\Input\InputDefinition
     + getNativeDefinition(): Symfony\Component\Console\Input\InputDefinition
     + addArgument($name, $mode, $description, $default): Symfony\Component\Console\Command\Command
     + addOption($name, $shortcut, $mode, $description, $default): Symfony\Component\Console\Command\Command
     + setName($name): Symfony\Component\Console\Command\Command
     + setProcessTitle($title): Symfony\Component\Console\Command\Command
     + getName(): string
     + setDescription($description): Symfony\Component\Console\Command\Command
     + getDescription(): string
     + setHelp($help): Symfony\Component\Console\Command\Command
     + getHelp(): string
     + getProcessedHelp(): string
     + setAliases($aliases): Symfony\Component\Console\Command\Command
     + getAliases(): array
     + getSynopsis(): string
     + getHelper($name): mixed
     + asText(): string
     + asXml($asDom): string|DOMDocument
     - validateName($name)

It is also possible to update the index for an specific file or folder. Therefor
you can use the update command, which only updates the definitions for the given
path

    phps update src/Foo

With the -j switch it is possible to return json instead of plain text which is
easier to parser for machines
