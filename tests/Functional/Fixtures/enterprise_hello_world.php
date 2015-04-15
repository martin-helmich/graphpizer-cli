<?php

namespace Big\Enterprise\Application {
	interface ApplicationInterface {

		public function run();
	}

	abstract class AbstractApplicationRunner {

		static public function createInstance() {
			return new \Big\Enterprise\Application\ConcreteApplicationRunner();
		}

		abstract public function run(ApplicationInterface $application);
	}

	class ConcreteApplicationRunner extends AbstractApplicationRunner {

		public function run(ApplicationInterface $application) {
			$application->run();
		}
	}
}

namespace Big\Enterprise\Output {
	interface Output {

		public function write($out);
	}

	class ConsoleOutput implements Output {

		public function write($out) {
			echo $out;
		}
	}

	abstract class AbstractOutputFactory {

		/**
		 * @return Output
		 */
		abstract public function getOutput();
	}

	class ConcreteOutputFactory extends AbstractOutputFactory {

		/**
		 * @return Output
		 */
		public function getOutput() {
			return new ConsoleOutput();
		}
	}
}

namespace Big\Enterprise\Greeter {
	interface GreeterInterface {

		/**
		 * @param string $who
		 * @return string
		 */
		public function greet($who);
	}

	interface CourtesyStrategy {

		/**
		 * @param string $statement
		 * @return string
		 */
		public function makeCourteous($statement);
	}

	class GermanGreeter implements GreeterInterface {

		/**
		 * @param string $who
		 * @return string
		 */
		public function greet($who) {
			return "Hallo $who!";
		}
	}

	class EnglishGreeter implements GreeterInterface {

		/**
		 * @param string $who
		 * @return string
		 */
		public function greet($who) {
			return "Hello $who!";
		}
	}

	class BritishGreeter implements GreeterInterface {

		/**
		 * @param string $who
		 * @return string
		 */
		public function greet($who) {
			return "Cheerio $who!";
		}
	}

	class GermanCourtesyStrategy implements CourtesyStrategy {

		/**
		 * @param string $statement
		 * @return string
		 */
		public function makeCourteous($statement) {
			return $statement . " Wie geht es Ihnen?";
		}
	}

	class BritishCourtesyStrategy implements CourtesyStrategy {

		/**
		 * @param string $statement
		 * @return string
		 */
		public function makeCourteous($statement) {
			return "Well met, old chap! " . $statement . " How are you faring on this fine day?";
		}
	}

	class CourteousGreeterDecorator implements GreeterInterface {

		/** @var GreeterInterface */
		private $wrapped;

		/**
		 * @var CourtesyStrategy
		 */
		private $courtesy;

		public function __construct(GreeterInterface $wrapped, CourtesyStrategy $courtesy) {
			$this->wrapped  = $wrapped;
			$this->courtesy = $courtesy;
		}

		/**
		 * @param string $who
		 * @return string
		 */
		public function greet($who) {
			$wrapped = $this->wrapped->greet($who);
			return $this->courtesy->makeCourteous($wrapped);
		}
	}

	abstract class AbstractGreeterFactory {

		abstract public function build($language);
	}

	class PlainGreeterFactory extends AbstractGreeterFactory {

		public function build($language) {
			if ($language == 'de_DE') {
				return new GermanGreeter();
			} else if ($language == 'en_GB') {
				return new BritishGreeter();
			} else {
				return new EnglishGreeter();
			}
		}
	}

	class CourteousGreeterFactory extends PlainGreeterFactory {

		public function build($language) {
			$greeter = parent::build($language);

			if ($language == 'de_DE') {
				$strategy = new GermanCourtesyStrategy();
			} else if ($language == 'en_GB') {
				$strategy = new BritishCourtesyStrategy();
			} else {
				return $greeter;
			}

			return new CourteousGreeterDecorator($greeter, $strategy);
		}
	}
}

namespace Big\Enterprise\HelloWorld {
	use Big\Enterprise\Application\ApplicationInterface;
	use Big\Enterprise\Greeter\AbstractGreeterFactory;
	use Big\Enterprise\Greeter\CourteousGreeterFactory;
	use Big\Enterprise\Greeter\GreeterInterface;
	use Big\Enterprise\Greeter\PlainGreeterFactory;
	use Big\Enterprise\Output\ConsoleOutput;
	use Big\Enterprise\Output\Output;

	interface HelloCommand {

		public function sayHelloTo($who);
	}

	class HelloCommandImpl implements HelloCommand {

		/**
		 * @var GreeterInterface
		 */
		private $greeter;

		/**
		 * @var Output
		 */
		private $output;

		public function __construct(GreeterInterface $greeter, Output $output) {
			$this->greeter = $greeter;
			$this->output  = $output;
		}

		public function sayHelloTo($who) {
			$this->output->write($this->greeter->greet($who));
		}
	}

	class ApplicationBuilder {

		protected $addressee = 'World';

		protected $courteous = FALSE;

		protected $language = 'en_US';

		/**
		 * @param string $addressee
		 * @return self
		 */
		public function setAddressee($addressee) {
			$this->addressee = $addressee;
			return $this;
		}

		/**
		 * @param boolean $courteous
		 * @return self
		 */
		public function setCourteous($courteous) {
			$this->courteous = $courteous;
			return $this;
		}

		/**
		 * @param string $language
		 * @return self
		 */
		public function setLanguage($language) {
			$this->language = $language;
			return $this;
		}

		public function build() {
			if ($this->courteous) {
				$builder = new CourteousGreeterFactory();
			} else {
				$builder = new PlainGreeterFactory();
			}

			return new Application($builder, $this->addressee, new ConsoleOutput(), $this->language);
		}
	}

	class Application implements ApplicationInterface {

		/**
		 * @var AbstractGreeterFactory
		 */
		private $greeterFactory;

		/**
		 * @var
		 */
		private $addressee;

		/**
		 * @var Output
		 */
		private $output;

		/**
		 * @var
		 */
		private $language;

		public function __construct(AbstractGreeterFactory $greeterFactory, $addressee, Output $output, $language) {
			$this->greeterFactory = $greeterFactory;
			$this->addressee = $addressee;
			$this->output = $output;
			$this->language = $language;
		}

		public function run() {
			$command = new HelloCommandImpl($this->greeterFactory->build($this->language), $this->output);
			$command->sayHelloTo($this->addressee);
		}
	}
}

namespace {
	use Big\Enterprise\Application\AbstractApplicationRunner;
	use Big\Enterprise\HelloWorld\ApplicationBuilder;

	$application = (new ApplicationBuilder())
		->setAddressee('World')
		->setLanguage('en_GB')
		->setCourteous(TRUE)
		->build();

	$runner = AbstractApplicationRunner::createInstance();
	$runner->run($application);
}