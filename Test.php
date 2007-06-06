<?php
# See the end of this file for documentation

register_shutdown_function('test_ends');

$__Test = array(
      'run'       => 0,
      'failed'    => 0,
      'badpass'   => 0,
      'planned'   => null
);

function plan( $plan, $why = '' )
{
    global $__Test;

    $__Test['planned'] = true;

    switch ( $plan )
    {
    case 'no_plan':
        $__Test['planned'] = false;
        break;
    case 'skip_all';
        printf( "1..0%s\n", $why ? " # Skip $why" : '' );
        exit;
    default:
        printf( "1..%d\n", $plan );
        break;
    }
}

function pass( $desc = '' )
{
    return proclaim(true, $desc);
}

function fail( $desc = '' )
{
    return proclaim( false, $desc );
}

function ok( $cond, $desc = '' ) {
    return proclaim( $cond, $desc );
}

function is( $got, $expected, $desc = '' ) {
    $pass = $got == $expected;
    return proclaim( $pass, $desc, /* todo */ false, $got, $expected );
}

function isnt( $got, $expected, $desc = '' ) {
    $pass = $got != $expected;
    return proclaim( $pass, $desc, /* todo */ false, $got, $expected, /* negated */ true );
}

function like( $got, $expected, $desc = '' ) {
    $pass = preg_match( $expected, $got );
    return proclaim( $pass, $desc,  /* todo */ false, $got, $expected );
}

function unlike( $got, $expected, $desc = '' ) {
    $pass = ! preg_match( $expected, $got );
    return proclaim( $pass, $desc,  /* todo */ false, $got, $expected, /* negated */ true );
}

function cmp_ok($got, $op, $expected, $desc = '')
{
    $pass = null;

    # See http://www.php.net/manual/en/language.operators.comparison.php
    switch ($op)
    {
    case '==':
        $pass = $got == $expected;
        break;
    case '===':
        $pass = $got === $expected;
        break;
    case '!=':
    case '<>':
        $pass = $got != $expected;
        break;
    case '!==':
        $pass = $got !== $expected;
        break;
    case '<':
        $pass = $got < $expected;
        break;
    case '>':
        $pass = $got > $expected;
        break;
    case '<=':
        $pass = $got <= $expected;
        break;
    case '>=':
        $pass = $got >= $expected;
        break;
    default:
        if ( function_exists( $op ) ) {
            $pass = $op( $got, $expected );
        } else {
            die("No such operator or function $op\n");
        }
    }

    return proclaim( $pass, $desc, /* todo */ false, $got, "$op $expected" );
}

function diag($message)
{
    if (is_array($message))
    {
        $message = implode("\n", $message);
    }

    foreach (explode("\n", $message) as $line)
    {
        echo "# $line\n";
    }
}

function include_ok( $file, $desc = '' )
{
    $pass = include $file;
    return proclaim( $pass, $desc == '' ? "include $file" : $desc );
}

function require_ok( $file, $desc = '' )
{
    $pass = require $file;
    return proclaim( $pass, $desc == '' ? "require $file" : $desc );
} 

function is_deeply( $got, $expected, $desc = '' )
{
    # Hack, this should recursively go over the datastructure and
    # report differences like Test::More does
    $s_got = serialize( $got );
    $s_exp = serialize( $expected );

    $pass = $s_got == $s_exp;

    proclaim( $pass, $desc, /* todo */ false, $got, $expected );
}

function isa_ok( $obj, $expected, $desc = '' ) {
    $name = get_class( $obj );
    $pass = $name == $expected;
    proclaim( $pass, $desc, /* todo */ false, $name, $expected );
} 

function proclaim(
    $cond, # bool
    $desc = '',
    $todo = false,
    $got = null,
    $expected = null,
    $negate = false ) {

    global $__Test;

    $__Test['run'] += 1;

    # TODO: force_todo

    # Everything after the first # is special, so escape user-supplied messages
    $desc = str_replace( '#', '\\#', $desc );
    $desc = str_replace( "\n", '\\n', $desc );

    $ok = $cond ? "ok" : "not ok";
    $directive = $todo === false ? '' : '# TODO aoeu';

    printf( "%s %d %s%s\n", $ok, $__Test['run'], $desc, $directive );

    if ( ! $cond ) {
        report_failure( $desc, $got, $expected, $negate, $todo );
    }

    return $cond;
}

function report_failure( $desc, $got, $expected, $negate, $todo ) {
    # Every public function in this file calls proclaim which then calls
    # this function, so our culprit is the third item in the stack
    $caller = debug_backtrace();
    $call = $caller['2'];

    diag(
        sprintf( " Failed%stest '%s'\n in %s at line %d\n       got: %s\n  expected: %s",
            $todo ? ' TODO ' : ' ',
            $desc,
            $call['file'],
            $call['line'],
            $got,
            $expected
        )
    );
}

function test_ends ()
{
    global $__Test;

    if ( $__Test['planned'] === false ) {
        printf( "1..%d\n", $__Test['run'] );
    }
}

/*

=head1 NAME

Test.php - TAP test framework for PHP with a L<Test::More>-like interface

=head1 SYNOPSIS

    #!/usr/bin/env php
    <?php  
    require 'Test.php';
  
    plan( $num ); # plan $num tests
    # or
    plan( 'no_plan' ); # We don't know how many
    # or
    plan( 'skip_all' ); # Skip all tests
    # or
    plan( 'skip_all', $reason ); # Skip all tests with a reason
  
    diag( 'message in test output' ) # Trailing \n not required
  
    # $test_name is always optional and should be a short description of
    # the test, e.g. "some_function() returns an integer"
  
    # Various ways to say "ok"
    ok( $got == $expected, $test_name );
  
    # Compare with == and !=
    is( $got, $expected, $test_name );
    isnt( $got, $expected, $test_name );
  
    # Run a preg regex match on some data
    like( $got, $regex, $test_name );
    unlike( $got, $regex, $test_name );
  
    # Compare something with a given comparison operator
    cmp_ok( $got, '==', $expected, $test_name );
    # Compare something with a comparison function (should return bool)
    cmp_ok( $got, $func, $expected, $test_name );
  
    # Recursively check datastructures for equalness
    is_deeply( $got, $expected, $test_name );
  
    # Always pass or fail a test under an optional name
    pass( $test_name );
    fail( $test_name );
    ?>
  
=head1 DESCRIPTION

F<Test.php> is an implementation of Perl's L<Test::More> for PHP. Like
Test::More it produces language agnostic TAP output (see L<TAP>) which
can then be gathered, formatted and summarized by a program that
understands TAP such as prove(1).

=head1 HOWTO

First place the F<Test.php> in the project root or somewhere else in
the include path where C<require> and C<include> will find it.

Then make a place to put your tests in, it's customary to place TAP
tests in a directory named F<t> under the root but they can be
anywhere you like. Make a test in this directory or one of its subdirs
and try running it with php(1):

    $ php t/pass.t 
    1..1
    ok 1 This dummy test passed

The TAP output consists of very simple output, of course reading
larger output is going to be harder which is where prove(1) comes
in. prove is a harness program that reads test output and produces
reports based on it:
    
    $ prove t/pass.t 
    t/pass....ok
    All tests successful.
    Files=1, Tests=1,  0 wallclock secs ( 0.03 cusr +  0.02 csys =  0.05 CPU)

To run all the tests in the F<t> directory recursively use C<prove -r
t>. This can be put in a F<Makefile> under a I<test> target, for
example:

    test: Test.php
		prove -r t
    
For reference the example test file above looks like this, the shebang
on the first line is needed so that prove(1) and other test harness
programs know they're dealing with a PHP file.

    #!/usr/bin/env php
    <?php
    
    require 'Test.php';
    
    plan(1);
    pass('This dummy test passed');
    ?>
    
=head1 TODO

Needs support for TODO tests, maybe via C<ok(0, "foo # TODO fix
this")> C<ok(1, "foo", array( 'todo' => 'fix this'))>.

=head1 SEE ALSO

L<TAP> - The TAP protocol

=head1 AUTHOR

E<AElig>var ArnfjE<ouml>rE<eth> Bjarmason <avar@cpan.org>

=head1 LICENSING

The author or authors of this code dedicate any and all copyright
interest in this code to the public domain. We make this dedication
for the benefit of the public at large and to the detriment of our
heirs and successors. We intend this dedication to be an overt act of
relinquishment in perpetuity of all present and future rights this
code under copyright law.

=cut

*/

?>
