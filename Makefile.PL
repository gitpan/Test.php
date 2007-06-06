use inc::Module::Install;

name 'Test.php';
version '0.02';

author 'Ævar Arnfjörð Bjarmason <avar@cpan.org>';
abstract_from 'Test.php';
license 'unrestricted';

auto_install;
WriteAll;

package MY;

sub test
{
    return <<'END'
test:
	prove -r t

END

}
