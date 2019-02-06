def exec(command, stdout_required = false)
  if stdout_required
    `#{command}`
  else
    system command
  end
end

desc 'run test'
task :spec do
  exec 'php composer.phar install --dev'
  exec './vendor/bin/phpunit -c test/phpunit.xml --coverage-html coverage'
  exec 'open coverage/index.html'
end

desc 'release new version'
task :release do
  require 'json'
  version = JSON.parse(File.read('composer.json'))['version']
  tags = exec('git tag', :stdout_required).split
  if tags.include?(version)
    puts "v.#{version} is already released."
  else
    puts "releasing v#{version} .."
    exec "git tag -a #{version}"
    exec "git push origin #{version}"
  end
end

task default: :spec