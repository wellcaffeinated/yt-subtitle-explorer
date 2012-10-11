<?php
namespace Heartsentwined\Test\FileSystemManager;

use Heartsentwined\FileSystemManager\FileSystemManager;
use Heartsentwined\FileSystemManager\Exception;

class FileSystemManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir('tmp')) mkdir('tmp', 0777);
        $this->wd = realpath(getcwd());
        chdir('tmp');

        mkdir('foo');
        touch('foo/foo1');
        touch('foo/foo2');
        mkdir('foo/bar');
        mkdir('foo/bar/bar');
        touch('foo/bar/bar1');
        mkdir('foo/baz');

        $this->user = 'www-data';
        $this->group = 'www-data';
    }

    public function tearDown()
    {
        try { rmdir('foo/baz'); } catch (\Exception $e) {}
        try { unlink('foo/bar/bar1'); } catch (\Exception $e) {}
        try { rmdir('foo/bar/bar'); } catch (\Exception $e) {}
        try { rmdir('foo/bar'); } catch (\Exception $e) {}
        try { unlink('foo/foo1'); } catch (\Exception $e) {}
        try { unlink('foo/foo2'); } catch (\Exception $e) {}
        try { rmdir('foo'); } catch (\Exception $e) {}

        chdir($this->wd);
    }

    public function testFileIterator()
    {
        $this->assertSame(array(
            'foo/bar/bar1',
            'foo/foo1',
            'foo/foo2',
        ), FileSystemManager::fileIterator('foo'));
    }

    public function testDirIterator()
    {
        $this->assertSame(array(
            'foo/bar/bar',
            'foo/bar',
            'foo/baz',
        ), FileSystemManager::dirIterator('foo'));
    }

    public function testRrmdir()
    {
        $this->assertTrue(FileSystemManager::rrmdir('foo'));
        $this->assertFalse(is_dir('foo'));
    }

    /**
     * @depends testRrmdir
     */
    public function testRcopy()
    {
        $this->assertTrue(FileSystemManager::rcopy('foo', 'bar'));
        $this->assertTrue(is_dir('bar'));
        $this->assertTrue(is_file('bar/foo1'));
        $this->assertTrue(is_file('bar/foo2'));
        $this->assertTrue(is_dir('bar/bar'));
        $this->assertTrue(is_dir('bar/bar/bar'));
        $this->assertTrue(is_file('bar/bar/bar1'));
        $this->assertTrue(is_dir('bar/baz'));

        FileSystemManager::rrmdir('bar');
    }

    /**
     * @depends testRrmdir
     */
    public function testRcopyExistingDir()
    {
        mkdir('bar');
        mkdir('bar/bar');

        $this->assertTrue(FileSystemManager::rcopy('foo', 'bar'));
        $this->assertTrue(is_dir('bar'));
        $this->assertTrue(is_file('bar/foo1'));
        $this->assertTrue(is_file('bar/foo2'));
        $this->assertTrue(is_dir('bar/bar'));
        $this->assertTrue(is_dir('bar/bar/bar'));
        $this->assertTrue(is_file('bar/bar/bar1'));
        $this->assertTrue(is_dir('bar/baz'));

        FileSystemManager::rrmdir('bar');
    }

    /**
     * @depends testRrmdir
     */
    public function testRcopyExistingFile()
    {
        mkdir('bar');
        touch('bar/foo1');

        $this->assertTrue(FileSystemManager::rcopy('foo', 'bar'));
        $this->assertTrue(is_dir('bar'));
        $this->assertTrue(is_file('bar/foo1'));
        $this->assertTrue(is_file('bar/foo2'));
        $this->assertTrue(is_dir('bar/bar'));
        $this->assertTrue(is_dir('bar/bar/bar'));
        $this->assertTrue(is_file('bar/bar/bar1'));
        $this->assertTrue(is_dir('bar/baz'));

        FileSystemManager::rrmdir('bar');
    }

    /**
     * @depends testRrmdir
     */
    public function testRchmod()
    {
        $this->assertFalse(is_dir('rchmod'));
        $this->assertFalse(is_dir('rchmod1'));
        $this->assertFalse(is_dir('rchmod2.f'));
        mkdir('rchmod/rchmod1', 0777, true);
        touch('rchmod/rchmod1/rchmod2.f');

        $this->assertTrue(
            FileSystemManager::rchmod('rchmod', 0444));
        $this->assertEquals('0444',
            substr(sprintf('%o', fileperms('rchmod')), -4));
        $this->assertEquals('0444',
            substr(sprintf('%o', fileperms('rchmod/rchmod1')), -4));
        $this->assertEquals('0444',
            substr(sprintf('%o', fileperms('rchmod/rchmod1/rchmod2.f')), -4));

        FileSystemManager::rrmdir('rchmod');
    }

    /**
     * @depends testRrmdir
     */
    public function testRchown()
    {
        $this->assertFalse(is_dir('rchown'));
        $this->assertFalse(is_dir('rchown1'));
        $this->assertFalse(is_dir('rchown2.f'));
        mkdir('rchown/rchown1', 0777, true);
        touch('rchown/rchown1/rchown2.f');

        $stat = stat('rchown');
        $user = posix_getpwuid($stat['uid']);
        $this->assertNotEquals($this->user, $user['name']);
        $stat = stat('rchown/rchown1');
        $user = posix_getpwuid($stat['uid']);
        $this->assertNotEquals($this->user, $user['name']);
        $stat = stat('rchown/rchown1/rchown2.f');
        $user = posix_getpwuid($stat['uid']);
        $this->assertNotEquals($this->user, $user['name']);

        $this->assertTrue(
            FileSystemManager::rchown('rchown', $this->user));
        $stat = stat('rchown');
        $user = posix_getpwuid($stat['uid']);
        $this->assertEquals($this->user, $user['name']);
        $stat = stat('rchown/rchown1');
        $user = posix_getpwuid($stat['uid']);
        $this->assertEquals($this->user, $user['name']);
        $stat = stat('rchown/rchown1/rchown2.f');
        $user = posix_getpwuid($stat['uid']);
        $this->assertEquals($this->user, $user['name']);

        FileSystemManager::rrmdir('rchown');
    }

    /**
     * @depends testRrmdir
     */
    public function testRchgrp()
    {
        $this->assertFalse(is_dir('rchgrp'));
        $this->assertFalse(is_dir('rchgrp1'));
        $this->assertFalse(is_dir('rchgrp2.f'));
        mkdir('rchgrp/rchgrp1', 0777, true);
        touch('rchgrp/rchgrp1/rchgrp2.f');

        $stat = stat('rchgrp');
        $user = posix_getgrgid($stat['gid']);
        $this->assertNotEquals($this->user, $user['name']);
        $stat = stat('rchgrp/rchgrp1');
        $user = posix_getgrgid($stat['gid']);
        $this->assertNotEquals($this->user, $user['name']);
        $stat = stat('rchgrp/rchgrp1/rchgrp2.f');
        $user = posix_getgrgid($stat['gid']);
        $this->assertNotEquals($this->user, $user['name']);

        $this->assertTrue(
            FileSystemManager::rchgrp('rchgrp', $this->group));
        $stat = stat('rchgrp');
        $user = posix_getgrgid($stat['gid']);
        $this->assertEquals($this->user, $user['name']);
        $stat = stat('rchgrp/rchgrp1');
        $user = posix_getgrgid($stat['gid']);
        $this->assertEquals($this->user, $user['name']);
        $stat = stat('rchgrp/rchgrp1/rchgrp2.f');
        $user = posix_getgrgid($stat['gid']);
        $this->assertEquals($this->user, $user['name']);

        FileSystemManager::rrmdir('rchgrp');
    }
}
